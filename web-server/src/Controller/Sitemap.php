<?php
namespace App\Controller;

use App\Classes\Dataset;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class Sitemap extends Controller
{

    const PAGE_SIZE = 500;
    const ROUTES_TYPE = "_routes";

    /**
     * @Route("/sitemap", name="sitemap", defaults={"_format"="xml"})
     * @Route("/sitemap/", name="sitemap2", defaults={"_format"="xml"})
     */
            //'asns' => $this->GET_JSON_objects('asns')
    public function sitemap_sitemapindex()
    {
        $routes = array(
            self::ROUTES_TYPE => 1
        );
        $types = array("asns","orgs");
        $dataset  = new Dataset();
        $dataset->GET_JSON();
        foreach ($types as &$type) {
            $total = self::GET_JSON_total($type);
            if ($total > 0) {
                $pages_num = intdiv($total, self::PAGE_SIZE) + 1;
                $routes[$type] = $pages_num;
            }
        }

        return $this->render('sitemap_sitemapindex.xml.twig', array(
            "lastmod" => $dataset->imported_year_mon_day()
            ,"routes" => $routes
        ));
    }

    /**
     * @Route("/sitemap/{type}/{page}", defaults={"_format"="xml"})
     * @Route("/sitemap/{type}", defaults={"_format"="xml","page"=1})
     */
    public function sitemap_urlset($type, $page)
    {
        $urls = NULL;
        $dataset  = new Dataset();
        $dataset->GET_JSON();
        $changefreq = "monthly";
        $priority = 1.0;
        $url_infos = array();
        if ($type == self::ROUTES_TYPE) {
            $urls = array("about","","contact", "asns/by-name/");
            foreach ($urls as &$url) {
                $url_info = array(
                    "loc" => $url,
                    "changefreq" => "monthly",
                    "lastmod" => $dataset->imported_year_mon_day(),
                    "priority" => .9);
                if ($url == "") {
                    $url_info["changefreq"] = "monthly";
                    $url_info["priority"] = 1.0;
                }
                array_push($url_infos, $url_info);
            }
        } else {
            array_push($url_infos, array(
                "loc" => $type."?page=".$page."&count=".self::PAGE_SIZE
                ,"changefreq" => "monthly"
                ,"lastmod" => $dataset->imported_year_mon_day()
                ,"priority" => .1
            ));

            $objects = self::GET_JSON_objects($type, $page);
            $urls_num = count($urls);
            foreach ($objects as $object) {

                $priority = 0;
                if (property_exists($object,"rank") ) {
                    $priority = ($dataset->{'asns'}-$object->{'rank'})/$dataset->{'asns'};
                }
                //if ($type == "asns") {
                    //$priority = sprintf("%.8f",$object->{'cone'}->{'asns'}/$dataset->{'asns'});
                //} elseif ($type == "orgs" and property_exists($object,"rank") ) {
                    //$priority = json_encode($object);
                //}

                array_push($url_infos, array(
                    "loc" => $type."/".$object->{'id'}
                    ,"changefreq" => "monthly"
                    ,"lastmod" => $dataset->imported_year_mon_day()
                    ,"priority" => $priority
                ));
            }
        }

        return $this->render('sitemap_urlset.xml.twig', array(
            "url_infos" => $url_infos
            ,"urls" => $urls
        ));
    }

    private function GET_JSON_objects($type, $page)
    {
        $url = getenv('RESTFUL_DATABASE_URL').'/'.$type.'?populate=1&page='.$page."&count=".self::PAGE_SIZE;
        $json = @file_get_contents($url);
        if ($json != false)
        {
            $parsed = json_decode($json);
            if (property_exists($parsed,'data')) {
                return $parsed->{'data'};
            }
        }
        return array();
    }

    private function GET_JSON_total($type)
    {
        $total = 0;
        $url = getenv('RESTFUL_DATABASE_URL').'/'.$type.'?page=1&count=1';
        $json = @file_get_contents($url);
        if ($json != false)
        {
            $parsed = json_decode($json);
            if (property_exists($parsed,'data') && property_exists($parsed,'total'))  
            {
                $total = $parsed->{'total'}; 
            }
        }
        return $total;
    }
}
