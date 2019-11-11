<?php

namespace Core\RecommenderBundle\Tests\Mocks;

use Core\RecommenderBundle\Service\AbstractRecommenderService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of RecommenderService
 *
 * @author mc26486
 */
class RecommenderService extends AbstractRecommenderService {

    public function recommend($queryString) {
        $jsonString = "";

        //$content = '[{"name":"Leadership of not-for-profit organizations in the aged and community care sector in Australia","url":"https:\/\/core.ac.uk\/display\/11048349"},{"name":"Analysing and modelling the effects of galactic cosmic rays on the Earth\u2019s atmosphere over daily timescales","url":"https:\/\/core.ac.uk\/display\/2709993"},{"name":"A convenient fog?: the creation of new Labour 1982\u20132010","url":"https:\/\/core.ac.uk\/display\/11049456"},{"name":"Short-term variability in satellite-derived cloud cover and galactic cosmic rays: an update","url":"https:\/\/core.ac.uk\/display\/2709414"}]';
        $content = '{"countLimit":5,"offset":0,"documents":[{"score":0.9013365,"year":2001,"name":"Almost as helpful as good theory: Some conceptual possibilities for the online classroom","id":262,"url":"https:\/\/core.ac.uk\/display\/262?widget=true&algorithm=moreLikeThis&parameters=%7B%22size%22%3A20%2C%22id%22%3A842%2C%22likeText%22%3Atrue%2C%22totalHits%22%3A692%2C%22totalTimeTaken%22%3A164%7D","authors":["Davis, Mike","Denning, Kate"]},{"score":0.8669235,"year":2007,"name":"The development of a theory-based intervention to promote appropriate disclosure of a diagnosis of dementia","id":978,"url":"https:\/\/core.ac.uk\/display\/978?widget=true&algorithm=moreLikeThis&parameters=%7B%22size%22%3A20%2C%22id%22%3A842%2C%22likeText%22%3Atrue%2C%22totalHits%22%3A692%2C%22totalTimeTaken%22%3A164%7D","authors":["Foy, Robbie","Francis, Jillian Joy","Johnston, Marie","Eccles, Martin P.","Lecouturier, Jan","Bamford, Claire","Grimshaw, Jeremy"]},{"score":0.8385536,"year":2004,"name":"Effects of rotation scheme on fishing behaviour with price discrimination and limited durability: Theory and evidence.","id":844,"url":"https:\/\/core.ac.uk\/display\/844?widget=true&algorithm=moreLikeThis&parameters=%7B%22size%22%3A20%2C%22id%22%3A842%2C%22likeText%22%3Atrue%2C%22totalHits%22%3A692%2C%22totalTimeTaken%22%3A164%7D","authors":["Seki, Erika"]},{"score":0.8371733,"year":2007,"name":"Impact on maternity professionals of novel approaches to clinical audit feedback","id":988,"url":"https:\/\/core.ac.uk\/display\/988?widget=true&algorithm=moreLikeThis&parameters=%7B%22size%22%3A20%2C%22id%22%3A842%2C%22likeText%22%3Atrue%2C%22totalHits%22%3A692%2C%22totalTimeTaken%22%3A164%7D","authors":["Cameron, Martin","Penney, G.","MacLennan, Graeme Stewart","McCann, S.","Walker, Anne"]},{"score":0.8354926,"year":2004,"name":"Ethnic enclaves and employment in England and Wales","id":845,"url":"https:\/\/core.ac.uk\/display\/845?widget=true&algorithm=moreLikeThis&parameters=%7B%22size%22%3A20%2C%22id%22%3A842%2C%22likeText%22%3Atrue%2C%22totalHits%22%3A692%2C%22totalTimeTaken%22%3A164%7D","authors":["Battu, Harminder","Mwale, Macdonald"]}],"dateLimit":3,"serverUrl":"https:\/\/core.ac.uk","count":10,"sortBy":"name","serverLogoUrl":"\/\/core.ac.uk\/images\/core_similar.png","url":"http:\/\/core.local\/display\/1"}';
        $responseCode = 200;

        //var_dump(json_decode($content));
        return json_decode($content);
    }

}
