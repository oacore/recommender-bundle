<?php

namespace Core\RecommenderBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Negotiation\LanguageNegotiator;

/**
 * Based on
 * @link https://stackoverflow.com/questions/46022235/symfony-3-detect-browser-language
 */
class SetLocaleListener {
    
    /* @var $variable array */

    private $supportedLanguages;

    public function __construct() {
        // USE hyphens - not underscores as we compare http accept headers
        // even though symfony users underscores
        $this->supportedLanguages = array("en", "de", "de-CH");
    }

    public function onKernelRequest(GetResponseEvent $event) {
        // Do not modify sub-requests
        if (KernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }
        // Only execute if we are in the /recommender path
        $path = $event->getRequest()->getPathInfo();
        if (strpos($path, '/recommender') !== 0) {
            return;
        }

        $language = "en";        
        /* @var $request \Symfony\Component\HttpFoundation\Request */
        $request = $event->getRequest();   
        $overrideLocaleKey = "forcelocale";
        if ($request->request->has($overrideLocaleKey) && in_array($request->request->get($overrideLocaleKey), $this->supportedLanguages)) {
            $language = $request->request->get($overrideLocaleKey);
        } else {        
            $language = $this->supportedLanguages[0];
            if (null !== $acceptLanguage = $event->getRequest()->headers->get('Accept-Language')) {
                $negotiator = new LanguageNegotiator();
                /* @var $best \Negotiation\AcceptHeader */
                $best = $negotiator->getBest(
                        $event->getRequest()->headers->get('Accept-Language'), $this->supportedLanguages
                );

                if (null !== $best) {
                    $language = $best->getValue();
                }
            }
        }
        // HTTP Accept header requires lang-countrycode, Symfony prefers lang_COUNTRYCODE
        // e.g. en-DE and en_GB or de-de and de_CH
        $language = str_replace("-", "_", $language);
        $request->setLocale($language);
    }

}
