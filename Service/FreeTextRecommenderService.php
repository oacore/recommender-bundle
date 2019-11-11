<?php

namespace Core\RecommenderBundle\Service;

use FOS\ElasticaBundle\Elastica\Client;

/**
 * Returns a list of ArticleMetadata based on input text
 * @author Samuel Pearce <samuel.pearce@open.ac.uk>
 */
class FreeTextRecommenderService {

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    
    /**
     *
     * @var string
     */
    private $recommenderParams;

    function __construct(\Psr\Log\LoggerInterface $logger, $recommenderParams) {
        $this->logger = $logger;
        $this->recommenderParams = $recommenderParams;
    }

    public function recommend($likeText) {

        $requestBody = http_build_query(array('text' => $likeText));
        
        try {
            $recommendation_endpoint = '/recommend/freetext';
            $buzzRequest = new \Buzz\Message\Request('POST', $recommendation_endpoint, $this->recommenderParams["backend.address"]);
            $buzzRequest->setContent($requestBody);
            $response = new \Buzz\Message\Response();
            $client = new \Buzz\Client\Curl();
            $client->setTimeout(5);
            $client->send($buzzRequest, $response);
            // Log this action on GA
            $response_status = substr($response->getHeaders()[0], 9, 3);
        } catch (Exception $var) {            
            throw new Exception("Backend exception", 0, $var);
        }
        $responseContent = json_decode($response->getContent(), true);
        if(isset($responseContent['documents'])) {
            return $responseContent['documents'];
        }
    }

}
