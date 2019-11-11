// jquery.coreWidget.js version 050916 
// Last modifier: vb4826
jq300 = jQuery.noConflict(true);
jq300(document).ready(function ($) {
    var algorithm = 'general';
    var userInput = {};
    
    // hopefully fix for IE, which doesnt support startsWith func
    if (!String.prototype.startsWith) {
        String.prototype.startsWith = function(searchString, position){
          position = position || 0;
          return this.substr(position, searchString.length) === searchString;
      };
    }

    $(document).on('click', '#coreRecommenderOutput .clearfix li', function (e) {
        e.preventDefault();
        if (!$(this).hasClass('active')) {
            $('#coreRecommenderOutput .clearfix li').removeClass('active');
            $(this).addClass('active');
            window.algorithm = this.id;
            $('#coreRecommender-tab1').toggle();
            $('#coreRecommender-tab2').toggle();
            //getSimilarities();
        }
    });

    $(document).on('mouseover', '#coreRecommenderOutput .dropdown-content a, #coreRecommenderOutput .dropdown .remove-article', function () {
        var idArticle = $(this).closest('.documentTpl').attr('id');
        $(".documentTpl#" + idArticle).attr('value', 'deactive');
    });

    $(document).on('mouseout', '#coreRecommenderOutput .dropdown-content a, #coreRecommenderOutput .dropdown .remove-article', function () {
        var idArticle = $(this).closest('.documentTpl').attr('id');
        $(".documentTpl#" + idArticle).removeAttr('value');
    });

    $(document).on('click', '.documentTpl', function (event) {
        var idArticle = $(this).attr('id');
        var link = $(this).attr('data-value');
        if ($(this).attr('value') != 'deactive') {
            window.open(link);
            event.stopPropagation();
            return false;
        }
    });

    $(document).on('click', '#coreRecommenderOutput .dropdown-content a', function () {
        var idArticle = $(this).closest('.documentTpl').attr('id');

        $(".documentTpl#" + idArticle).closest('li').remove();
        if ($('.coreRecommender > ul li').length === '0') {
            $('.coreRecommender').html('<div class="error">No more recommendations available for this document. Please reload the page.</div>');
        }

        var operationType = $(this).attr('class');
        var serverUrl = 'https://core.ac.uk/recommender/removeArticle';
        var recommendedUrl = window.location.href;
        var recommendation_url = $(this).attr('data-value');

        $.ajax({
            url: serverUrl,
            crossDomain: true,
            data: {
                "recommendation_url": recommendation_url,
                "severity": operationType,
                "referer": recommendedUrl,
                "idArticle": idArticle
            },
            timeout: 1200000,
            success: function (data) {
            },
            error: function (data) {
                $('#coreStatusMessage').html('<div class="error">Sorry we are not able to process your request at the moment. Please try again later.</div>');
            }
        });
    });

    function checkInputParams() {
        if (localStorage.getItem('userInput') !== null || localStorage.getItem('userInput') !== undefined)
            userInput = JSON.parse(localStorage.getItem('userInput'));
    }

    function loadingPage(locale) {
        if ("none" === locale || locale === "") {
            var serverUrl = 'https://core.ac.uk/recommender/main';
        } else {
            var serverUrl = 'https://core.ac.uk/' + locale + '/recommender/main';
        }

        $.ajax({
            url: serverUrl,
            crossDomain: true,
            data: {
            },
            timeout: 1200000,
            success: function (data) {
                $("#coreRecommenderOutput").html(data);
            },
            error: function (data) {
                $('#coreStatusMessage').html('<div class="error">Sorry the recommender service is unavailable at the moment.</div>');
            }
        });
    }

    function getSimilarities() {
        // What language to load the recommender in
        var locale = "none"; 
        if (localStorage.getItem('overridelocale') !== null && localStorage.getItem('overridelocale') !== undefined) {
            locale = localStorage.getItem('overridelocale');
        } else if (document.documentElement.lang !== null) {
            locale = document.documentElement.lang;
            locale = locale.replace("-", "_");
            if (locale.indexOf("_") >= 0 ) {
                locale = locale.split("_")[0];
            }
        } 
        
        loadingPage(locale);
        checkInputParams();

        var documentOAI;
        var documentUrl;
        var documentTitle;
        var documentAuthors;
        var documentAbstract;

        userInput.documentOAI === undefined ? documentOAI = getProperty("documentOAI") : documentOAI = userInput.documentOAI;
        userInput.documentUrl === undefined ? documentUrl = getProperty("documentUrl") : documentUrl = userInput.documentUrl;
        userInput.documentTitle === undefined ? documentTitle = getProperty("documentTitle") : documentTitle = userInput.documentTitle;
        userInput.documentAuthors === undefined ? documentAuthors = getProperty("documentAuthors") : documentAuthors = userInput.documentAuthors;
        userInput.documentAbstract === undefined ? documentAbstract = getProperty("documentAbstract") : documentAbstract = userInput.documentAbstract;
        $(function () {
            $("#coreRecommenderOutput").recommend({
                documentOAI: documentOAI,
                documentUrl: documentUrl,
                documentTitle: documentTitle,
                documentAuthors: documentAuthors,
                documentAbstract: documentAbstract,
                forcelocale: locale                
            });
        });
    }
    getSimilarities();

    function getProperty(property) {
        var result;
        switch (property) {
            case "documentAuthors":
                result = getPropertyContent("DC.creator");
                if (result == "")
                    result = getPropertyContent("DCTERMS.creator");
                if (result == "")
                    result = getPropertyContent("eprints.creators_name");
                if (result == "")
                    result = getPropertyContent("citation_author");
                return result;
            case "documentTitle":
                result = getPropertyContent("DC.title");
                if (result == "")
                    result = getPropertyContent("DCTERMS.title");
                if (result == "")
                    result = getPropertyContent("eprints.title");
                if (result == "")
                    result = getPropertyContent("citation_title");
                if (result == "")
                    result = getPropertyContent("og:title");
                return result;
            case "documentAbstract":
                result = getPropertyContent("DC.description");
                var dctermsAbstract = getPropertyContent("DCTERMS.abstract");
                var eprintsAbstract =  getPropertyContent("eprints.abstract");
                if (dctermsAbstract !=="" && dctermsAbstract !== result){
                    result = result + " " + dctermsAbstract;
                } 
                if (eprintsAbstract !=="" && eprintsAbstract !== result){
                    result = result + " " + eprintsAbstract;
                } 
                if (result === "") {
                    result = getPropertyContent("DCTERMS.abstract");
                }
                if (result === "") {
                    result = getPropertyContent("eprints.abstract");
                }
                if (result === "") {
                    console.log("in og:desc")
                    result = getPropertyContent("og:description");
                }

                return result;
            case "documentUrl":
                result = getPropertyContent("DC.identifier");
                if (result == "") {
                    result = getPropertyContent("DCTERMS.identifier");
                }
                if (result == "") {
                    result = getPropertyContent("eprints.document_url");
                }
                if (result == ""){
                    console.log("in url")
                    result = getPropertyContent("og:url")
                }
                console.log("url" + result)
                if (result.startsWith("http")){

                    return result;
                }else {
                    return "";
                }
            case "documentOAI":
                var prefix = "oai:";
                var property = "eprints.eprintid";
                var hostname = window.location.hostname;
                // TODO must be changed! only for testing!
                //var hostname = "open.ac.uk.OAI2";
                var eprintId = getPropertyContent(property);
                if (eprintId != "") {
                    return prefix + hostname + ":" + eprintId;
                } else if (getPropertyContent("DC.identifier") != null) {
                    var identifier = getPropertyContent("DC.identifier");
                    if (identifier.startsWith("oai:")) {
                        return identifier;
                    }
                }
                return "";
            default:
                var DCprefix = "DC.";
                var DCTERMSprefix = "DCTERMS.";
                var eprintsPrefix = "eprints.";
                property = DCprefix.concat(property);
                result = getPropertyContent(property);
                if (result == "") {
                    property = DCTERMSprefix.concat(property);
                    result = getPropertyContent(property);
                }
                if (result == "") {
                    property = eprintsPrefix.concat(property);
                    result = getPropertyContent(property);
                }
                return result;
        }
    }

    function getPropertyContent(property) {
        var metas = document.getElementsByTagName('meta');

        switch (property) {
            case "DC.creator":
            case "DCTERMS.creator":
            case "eprints.creators_name":
            case "citation_author":
                var authors = [];
                for (var i = 0; i < metas.length; i++) {
                    if (metas[i].getAttribute("name") == property) {
                        authors.push(metas[i].getAttribute("content"));
                    }
                }
                return authors;
            default:

                for (var i = 0; i < metas.length; i++) {


                    if (metas[i].getAttribute("name") === property || metas[i].getAttribute("property")===property) {
                        console.log(property+" Found " + metas[i].getAttribute("content"))
                        return metas[i].getAttribute("content");
                    }
                }
                return "";
        }
    }
});

(function ($) {
    $.fn.recommend = function (options) {
        serverUrl = 'https://core.ac.uk/recommender/recommend';
      
        var settings = {
            "serverUrl": serverUrl,
            "algorithm": window.algorithm,
            "countLimit": 5,
            "dateLimit": 3,
            "sortBy": "name",
            "idRecommender": localStorage.getItem('idRecommender'),
            "referer": window.location.href,
        };
        
        // extend settings if the override is available
        if (options) {
            $.extend(settings, options);
        }
        
        var xhr = null;
        // URL or abstract have to be specified
        xhr = $.ajax({
            url: settings.serverUrl,
            crossDomain: true,
            method:"POST",
            data: {
                oai: settings.documentOAI,
                url: settings.documentUrl,
                title: settings.documentTitle,
                authors: settings.documentAuthors,
                aabstract: settings.documentAbstract,
                idRecommender: settings.idRecommender,
                countLimit: settings.countLimit,
                dateLimit: settings.dateLimit,
                sortBy: settings.sortBy,
                algorithm: settings.algorithm,
                referer: settings.referer,
                forcelocale: settings.forcelocale,
            },
            timeout: 1200000,
            success: function (data) {
                $("#coreRecommenderOutput").html(data);
                $('#coreRecommender-tab1').show();
                $('#coreRecommender-tab2').hide();
            },
            error: function (error) {
                if (error.getResponseHeader("X-Core-Recommender-Error")) {
                    $("#coreRecommenderOutput").html('<div class="error">Plugin configuration error: ' + error.getResponseHeader("X-Core-Recommender-Error") + '</div>');

                } else {
                    $("#coreRecommenderOutput").html('<div class="error">Sorry the service is unavailable at the moment. Please try again later.</div>');
                }
            }
        });
    };
})(jq300);