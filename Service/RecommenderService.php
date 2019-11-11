<?php

namespace Core\RecommenderBundle\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description of RecommenderService
 *
 * @author vb4826
 */
class RecommenderService {

    private $recommenderParams;
    private $googleAnalyticsService;
    private $logger;

    function __construct($logger, $recommenderParams, $googleAnalyticsService) {
        $this->logger = $logger;
        $this->recommenderParams = $recommenderParams;
        $this->googleAnalyticsService = $googleAnalyticsService;
    }

    public function invalidateCache($source_url) {
        try {
            $recommendation_endpoint = '/cache-invalidate?source_url='. urlencode($source_url);
            $buzzRequest = new \Buzz\Message\Request('GET', $recommendation_endpoint, $this->recommenderParams["backend.address"]);
            $response = new \Buzz\Message\Response();
            $client = new \Buzz\Client\Curl();
            $client->setTimeout($this->recommenderParams["timeout"]);
            $client->send($buzzRequest, $response);
            
        } catch (Exception $var) {
            //Catch all exceptions and fail silently
            return $this->createNotFoundException("Could not invalidate cache");
        }
    }

    public function recommend(Request $request, $queryString, $noLog = false) {
        try {
            $recommendation_endpoint = '/recommend';
            $buzzRequest = new \Buzz\Message\Request('POST', $recommendation_endpoint, $this->recommenderParams["backend.address"]);
            $buzzRequest->setContent($queryString);
            $response = new \Buzz\Message\Response();
            $client = new \Buzz\Client\Curl();
            $client->setTimeout($this->recommenderParams["timeout"]);
            $client->send($buzzRequest, $response);
            // Log this action on GA
            $response_status = substr($response->getHeaders()[0], 9, 3);
            if (!$noLog) {
                $this->logRecommendationGA($request, $recommendation_endpoint, $response_status);
            }
        } catch (Exception $var) {
            //Catch all exceptions and fail silently
            return $this->createNotFoundException("Could not fetch similar documents from backend server");
        }
        $headers = array(
            'Content-Type' => 'application/json'
        );

        $content = $response->getContent();
        $responseCode = $response->getStatusCode();

        if ($responseCode == 401) {
            $content = "You are not authorised. Please check your recommender ID.";
        } elseif ($response->getContent() == false) {
            $content = "Error. The upstream server responded with no content ";
            $responseCode = 503;
        }

        $response = new Response($content, $response->getStatusCode(), $headers);

        return $response;
    }

    public function logRecommendationGA(Request $request, $recommendation_endpoint, $response_status) {
        $url = parse_url($request->server->get('HTTP_REFERER'));
        $host = "unknown";
        if (isset($url['host'])) {
            $host = $url['host'];
        }

        // As a client ID, we will assign the hostname as in these events, we want to see the 
        // frequency and the id of the websited calling our recommender.
        $gaPayload = array(
            't' => 'event',
            'ec' => 'RecommenderCall',
            'ea' => $response_status,
            'el' => $recommendation_endpoint,
            'uip' => $request->getClientIp(),
            'ua' => $request->server->get('HTTP_USER_AGENT'),
            'dr' => $request->server->get('HTTP_REFERER'),
            // 'ua' => $request->headers->get('User-Agent'),
            // 'dr' => isset($queryParams['referer']) ? $queryParams['referer'] : "unknown",
            'cid' => $host
        );

        $this->googleAnalyticsService->send($gaPayload, 'event');
    }

}
