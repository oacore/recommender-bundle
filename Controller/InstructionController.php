<?php

namespace Core\RecommenderBundle\Controller;

use Core\CommonBundle\Controller\BaseController;

/**
 * Description of AjaxController
 *
 * @author vb4826
 */
class InstructionController extends BaseController {

    public function generalInstructionAction() {
        $responseArray = array();
        return $this->render('CoreRecommenderBundle:Recommender:recInstruction.html.twig', $responseArray);
    }
    
    public function specificInstructionAction($idRecommender) {
        $responseArray = array();
        $recommenders = $this->getDoctrine()
                ->getRepository('CoreCommonBundle:RecommenderInstance')
                ->findByIdRecommender($idRecommender);
        if(!empty($recommenders) && $recommenders != null){
            $responseArray['recommenders'] = array();
            $responseArray['recommenders'][0]["idRecommender"] = $recommenders[0]->getIdRecommender();
        }
        return $this->render('CoreRecommenderBundle:Recommender:recInstruction.html.twig', $responseArray);
    }
}
