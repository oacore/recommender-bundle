<?php

namespace Core\RecommenderBundle\Controller;

use Core\CommonBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Description of AjaxController
 *
 * @author vb4826
 */
class RecommendController extends BaseController {

    private $recommenderService;
    private $CORE_DISPLAY_PAGE_ID = "ffffff";

    private $t;
    
    public function recommendAction(Request $request) {
        $this->t = $this->get('translator');
        /* @var $t \Symfony\Component\Translation\Translator */
        $responseArray = array();
        $articlesDetails = array();
        $recommedations = array();

        $recommenderService = $this->get("core.recommender");

        $queryParams = $request->request->all();
		/*
		 * The above does not guarrantee that the parameters will be retrieved (on GET requests) 
		 * so try and retrieve them directly from the URI (by restructuring it)
		 */
		if(count($queryParams)==0){
			parse_str(parse_url($request->getHost() . $request->getRequestUri())['query'], $queryParams);
		}
		
        // deleting characters after hash from url referer adress
        if(isset($queryParams["referer"]) && strpos($queryParams['referer'], '#')){
            $removeHashFromUrl = strstr($queryParams['referer'], '#', true);
            $queryParams['referer'] = $removeHashFromUrl;
		}
        $errorMessage = $this->checkMissingParameters($queryParams);
        if ($errorMessage != false) {
            $responseArray['error'] = "CORE does not have enough data to provide recommendations about this document. ("
                    . $errorMessage . " is missing)";
            return new \Symfony\Component\HttpFoundation\Response($this->render('CoreRecommenderBundle:Recommender:main.html.twig', $responseArray), 400);
        }

        $idRecommender = $queryParams["idRecommender"];
        // getting recommender parameters from database by id
        $recommenderParams = $this->getRecommenderParams($idRecommender);
        $idRepository = 0;
        if (!empty($recommenderParams["idRepository"])) {
            $idRepository = $recommenderParams["idRepository"];
        }
        if ($idRepository != 0) {
            $repository = $this->getElasticSearch()->getRepositoryById($idRepository);
            if (empty($repository)) {
                $repository = $this->getDoctrine()
                        ->getRepository("CoreCommonBundle:DashboardRepo")
                        ->findById($idRepository);
            }
            $backendRequestParams = array_merge($queryParams, $recommenderParams);
            $backendRequestParams['algorithm'] = 'specificLibrary';


            $repositoryRecommendations = $this->requestRecommendationFromBackend($request, $backendRequestParams);
            if (!is_object($repositoryRecommendations)) {
                return $this->generateErrorPage($repositoryRecommendations["errorCode"]);
            }
        }
				
        $backendRequestParams = array_merge($queryParams, $recommenderParams);
        $backendRequestParams["idRepository"] = null;
        $backendRequestParams['algorithm'] = 'general';
        $recommendations = $this->requestRecommendationFromBackend($request, $backendRequestParams);
        if (!is_object($recommendations)) {
            return $this->generateErrorPage($recommendations["errorCode"]);
        }
        if ($idRepository != 0) {
            $responseArray["nameRepository"] = $repository->getName();
            $responseArray['specificArticles'] = $repositoryRecommendations;
        }

        if (empty($recommendations->documents)){
            return $this->generateErrorPage(204);
        }
        
        $responseArray['generalArticles'] = $recommendations;
        $responseArray['selectedTab'] = "general";

        $recommendation_endpoint = '/recommend';
//        $this->logRecommendationES($request, $recommendation_endpoint, $responseArray);
        return $this->render('CoreRecommenderBundle:Recommender:main.html.twig', $responseArray);
    }

    function generateErrorPage($errorCode) {
        $responseArray['error'] = $this->errorHandler($errorCode);
        return $this->render('CoreRecommenderBundle:Recommender:main.html.twig', $responseArray);
    }

    function requestRecommendationFromBackend(Request $request, $backendRequestParams) {
        $queryString = http_build_query($backendRequestParams);
        $jsonResponse = $this->getRecommenderService()->recommend($request, $queryString);
        $statusCode = $jsonResponse->getStatusCode();
        if ($statusCode != 200) {
            return array("errorCode" => $statusCode);
        } else {
            $recommendation = json_decode($jsonResponse->getContent());
            return $recommendation;
        }
    }

    public function errorHandler($statusCode) {
        if ($statusCode == 204) {
            return $this->t->trans("not_enough_date_to_generate_recommendations");
        } else if ($statusCode == 401) {
            return"You are not authorised. Please check your recommender ID.";
        } else if ($statusCode == 500) {
            return "Sorry CORE is not able to provide recommendations at the moment,"
                    . " please try again later";
        } else {
            return $this->t->trans("unable_to_process_request_unspecified_error");
        }
    }

    /*
     * Saving reported article to blacklist in DB
     */

    public function removeArticleAction(Request $request) {
        $this->t = $this->get('translator');
        $recommenderService = $this->get("core.recommender");

        // getting query string from request
        $queryString = $request->getQueryString();
        $db_service = $this->getDatabaseService();
        parse_str($queryString, $queryParams);

        $url = $queryParams['recommendation_url'];
        $params = parse_url($url)['query'];

        parse_str($params, $url_parameters);

        // deleting characters after hash from url referer adress
        if (strpos($queryParams['referer'], '#')) {
            $removeHashFromUrl = strstr($queryParams['referer'], '#', true);
            $queryParams['referer'] = $removeHashFromUrl;
        }
        $source_url = $queryParams['referer'];

        //invalidate cached recommendations
        $recommenderService->invalidateCache($source_url);
        
        $core_article_id = $queryParams['idArticle'];
        $recommendation_source_id = $url_parameters['source'];
        $recommendation_algorithm_id = $url_parameters['algorithmId'];
        $recommendation_similar_to_doc = $url_parameters['similarToDoc'];
		$recommendation_similar_to_doc_key = $url_parameters['similarToDocKey'];
        $severity = $queryParams['severity'];

        $db_service->insertRecommendationComplaint($source_url, $core_article_id, $recommendation_source_id, $recommendation_algorithm_id, $recommendation_similar_to_doc, $severity, $recommendation_similar_to_doc_key);

        return new JsonResponse(array('status' => 'ok'));
    }

    function getRecommenderService() {
        if (empty($this->recommenderService)) {
            $this->recommenderService = $this->container->get("core.recommender");
        }
        return $this->recommenderService;
    }

    function checkMissingParameters($paramsArray) {

        if (!isset($paramsArray["title"]) || $paramsArray["title"] == null) {
            return "Title";
        }
        return false;
    }

    function getRecommenderParams($idRecommender) {
        $recommenderParams = array();

        if ($idRecommender === $this->CORE_DISPLAY_PAGE_ID) {
            $recommenderParams = array(
                "recType" => "core-display",
                "idRepository" => ""
            );
        } else {
            $recommender = $this->getDoctrine()
                    ->getRepository('CoreCommonBundle:RecommenderInstance')
                    ->findByIdRecommender($idRecommender);

            if (!$recommender || empty($recommender)) {
                $message = "Recommender ID not found";
                if (strlen($idRecommender) == 32) {
                    $message = "Recommender ID entered looks like an API key, please register for a recommender ID. Please check the documentation for finding your Recommender ID or contact support.";
                }
                if (strlen($idRecommender) > 6) {
                    $message = "Recommender ID is too long, Recommender ID's are exactly 6 characters long. Please check your Recommender ID or contact support";
                }
                if (strlen($idRecommender) < 6) {
                    $message = "Recommender ID is too short, Recommender ID's are exactly 6 characters long. Please check your Recommender ID or contact support";
                }
                throw new HttpException(401, 'Recommender with Id (' . $idRecommender . ') does not exist.', null, array("X-Core-Recommender-Error" => $message));
            }

            $recommenderParams = array(
                "recType" => $recommender[0]->getType(),
                "idRepository" => $recommender[0]->getIdRepository()
            );
        }

        return $recommenderParams;
    }

	public function logRecommendationES(Request $request, $recommendation_endpoint, $responseArray) {
		$host_name = $request->headers->get('host');
		$ctrTracker = $this->getCTRTracker();
		
		if ($ctrTracker !== null) {
            $ctrTracker->logRecommenderImpression($request, $recommendation_endpoint, $host_name, $responseArray);
        } else {
            $this->getLogger()->warn("ElasticSearch service could not be reached "
                    . "during recommendation impression logging.");
        }
	}
}
