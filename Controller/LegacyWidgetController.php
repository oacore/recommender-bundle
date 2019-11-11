<?php

namespace Core\RecommenderBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

// SP: HACK, the old recommender tomcat service just does not work with the new
// index. Patch together using the new recommendation service
// SP: Sorry, I move this to RecommenderBundle so save loading a whole bundle just for
// 1 controller - This controller now shares stuff with the RecommenderBundle now anyway
class LegacyWidgetController extends RecommendController {

    /**
     * 
     * @param type $id
     * @param type $status
     * @param type $parts
     * @return type
     */
    public function Widget2Action(Request $request) {
        $responseArray = array();
        $articlesDetails = array();
        $recommedations = array();

        $recommenderService = $this->get("core.recommender");

        $queryParams = $request->query->all();
        /*
         * The above does not guarrantee that the parameters will be retrieved (on GET requests) 
         * so try and retrieve them directly from the URI (by restructuring it)
         */
        if (count($queryParams) == 0) {
            parse_str(parse_url($request->getHost() . $request->getRequestUri())['query'], $queryParams);
        }

        // deleting characters after hash from url referer adress
        if (isset($queryParams["referer"]) && strpos($queryParams['referer'], '#')) {
            $removeHashFromUrl = strstr($queryParams['referer'], '#', true);
            $queryParams['referer'] = $removeHashFromUrl;
        }
        $errorMessage = $this->checkMissingParameters($queryParams);
        if ($errorMessage != false) {
            $responseArray['error'] = "CORE does not have enough data to provide recommendations about this document. ("
                    . $errorMessage . " is missing)";
            return new \Symfony\Component\HttpFoundation\Response($this->render('CoreRecommenderBundle:Recommender:main.html.twig', $responseArray), 400);
        }

        $idRecommender = $queryParams["api_key"];

        $backendRequestParams = $queryParams;
        $backendRequestParams["idRepository"] = null;
        $backendRequestParams['algorithm'] = 'general';
        $recommendationsResponse = $this->requestRecommendationFromBackend($request, $backendRequestParams);
        if (!is_object($recommendationsResponse)) {
            return $this->generateErrorPage($recommendations["errorCode"]);
        }

        if (empty($recommendationsResponse->documents)) {
            return new \Symfony\Component\HttpFoundation\Response("No documents found", 200);
        }

        // Fixes issue where old recommender is actually expecting the title as 'name'        
        foreach ($recommendationsResponse->documents as $key => $value) {
            $title = $recommendationsResponse->documents[$key]->title;
            unset($recommendationsResponse->documents[$key]->title);
            $recommendationsResponse->documents[$key]->name = $title;

            $recommendationsResponse->documents[$key]->url = "https://core.ac.uk" . $recommendationsResponse->documents[$key]->url;
            unset($recommendationsResponse->documents[$key]->repositoryName);
            unset($recommendationsResponse->documents[$key]->publisher);
            unset($recommendationsResponse->documents[$key]->simhash);
            unset($recommendationsResponse->documents[$key]->language);

            $recommendationsResponse->documents[$key]->id = (int) $recommendationsResponse->documents[$key]->id;
            $recommendationsResponse->documents[$key]->year = (int) $recommendationsResponse->documents[$key]->year;
        }


        $headers = array(
            'Content-Type' => 'application/json'
        );

        $recommendations['countLimit'] = 10;
        $recommendations['offset'] = 0;
        $recommendations['documents'] = $recommendationsResponse->documents;
        $recommendations['dateLimit'] = 3;
        $recommendations['serverUrl'] = "https://core.ac.uk";
        $recommendations['count'] = 10;
        $recommendations['serverLogoUrl'] = "https://core.ac.uk/images/core_similar.png";
        $responseData = json_encode($recommendations);
        $responseData = $request->query->get('callback') . "(" . json_encode($recommendations) . ");";


        $response = new \Symfony\Component\HttpFoundation\Response($responseData, 200, $headers);
        return $response;
    }

    private function createGetKeyValue(Request $req, $valuename) {
        return $valuemame . "=" . $request->query->get('callback') . "&";
    }

}
