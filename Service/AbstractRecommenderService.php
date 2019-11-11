<?php

namespace Core\RecommenderBundle\Service;

/**
 * Description of AbstractRecommenderService
 *
 * @author mc26486
 */
abstract class AbstractRecommenderService {

    public abstract function recommend($queryString);
}
