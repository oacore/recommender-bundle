<?php

namespace Core\RecommenderBundle\Controller;

use Core\CommonBundle\Controller\BaseController;

/**
 * Description of MainController
 *
 * @author vb4826
 */
class MainController extends BaseController {

    public function mainAction() {
        $responseArray["provider"] = "ORO";
        return $this->render('CoreRecommenderBundle:Recommender:main.html.twig', $responseArray);
    }

}
