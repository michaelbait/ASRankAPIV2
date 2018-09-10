<?php
// src/Controller/ASN.php
namespace App\Controller;

use App\Classes\ASN_Information;
use App\Classes\Location;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AsnController extends Controller
{
    const PAGE_SIZE = 40;
    /**
     * @Route("/", name="default")
     */
    public function asns_top_ten(Request $request) 
    {
        return $this->asns($request, True);
    }
    /**
     * @Route("/asns", name="asns_ranking")
     */
    public function asns(Request $request, $top_ten=False)
    {
        $mode0 = $request->query->get('mode0');
        if ($mode0 != null) {
            if ($mode0 == "as-ranking") {
                return $this->redirectToRoute('default', array(), 301);
            } 
            if ($mode0 == "as-info") {
                $asn = $request->query->get('as');
                if ($asn != null) {
                    return $this->redirectToRoute('asn_information', array(
                        "asn"=>$asn
                    ), 301);
                }
            }
        } 
        $params = $request->query->all();
        $page = $request->query->get('page');
        $sort_type = $request->query->get('sort_type');
        $sort_dir = $request->query->get('sort_dir');
        $page_size = $request->query->get('count');
        if ($page_size == null) {
            $page_size = self::PAGE_SIZE;
        }

        $params_num = sizeof($params);
        $valid_params = array("page","sort_type","sort_dir","count");
        foreach ($valid_params as $param) {
            if ($request->query->get($param) != null) {
                $params_num -= 1;
            }
        }

        if ($params_num > 0) {
            throw new NotFoundHttpException();
        }
        $page = $this->digit_santizer($page, 1);
        return $this->render('asns/asns.html.twig', array(
            'top_ten' => $top_ten
            ,'page' => $page
            ,'sort_type' => $sort_type
            ,'sort_dir' => $sort_dir
            ,'page_size' => $page_size
            ,'org' => false
        ));
    }

    /**
     * @Route("/asns/{asn}", name="asn_neighbors")
     * @Route("/asns/{asn}/neighbors")
     * @Route("/asns/{asn}/as-core", name="asn_as_core", defaults={"area"="as-core"})
     */
    public function asn_neighbors(Request $request, $asn="", $area="neighbors")
    {
        if (!preg_match("/^\d+$/", $asn)) {
            return $this->asn_search($request, $asn);
        }

        $page = $this->digit_santizer($request->query->get('page'), 1);
        $sort_type = $request->query->get('sort_type');
        $sort_dir = $request->query->get('sort_dir');
        
        $asn_info = new ASN_Information($asn);
        $asn_info->GET_JSON();

        $location = new Location("asn",$area,$asn_info);

        return $this->render('asns/asn.html.twig', array(
            'asn_info' => $asn_info
            ,'page' => $page
            ,'sort_type' => $sort_type
            ,'sort_dir' => $sort_dir
            ,'page_size' => self::PAGE_SIZE
            ,'org' => false
            ,'location'=> $location
        ));
    }

    /**
     * @Route("/asns/", name="asn_search")
     * @Route("/asns/by-name")
     * @Route("/asns/by-name/")
     * @Route("/asns/by-name/{name}")
     */
    public function asn_search(Request $request, $name = NULL)
    {
        $page = $this->digit_santizer($request->query->get('page'), 1);
        if ($name == NULL) {
            $name = $request->query->get('name');
        }
        $type = $request->query->get('type');
        //if ($type != NULL && strcmp($type,"go+to") == 0) {
        //if ($type != NULL && strcmp($type,"go to") == 0 && (preg_match("/^\d+$/", $name) || preg_match("/^asn?(\d+)$/i", $name))) {
        //if (($type == NULL || strcmp($type,"go to") == 0) && (preg_match("/^\d+$/", $name) || preg_match("/^asn?(\d+)$/i", $name))) {
        if (preg_match("/^\d+$/", $name) || preg_match("/^asn?(\d+)$/i", $name)) {
            $name = preg_replace("/\D/", '', $name);
            return $this->asn_neighbors($request, $name);
        }

        return $this->render('asns/asn_search.html.twig', array(
            'name' => $name
            ,'page' => $page
            ,'page_size' => self::PAGE_SIZE
        ));
    }

    /*
     * Used to sanitize digits
     */
    private function digit_santizer($digit, $default=0)
    {
        if ($digit == NULL || !preg_match("/^\d+$/", $digit)) {
            $digit = $default;
        }
        return $digit;
    }

    private function GET_JSON($asn)
    {
        $json = file_get_contents(getenv('RESTFUL_DATABASE_URL').'/asns/'.$asn.'?populate=1');
        if ($json != NULL)
        {
            $parsed = json_decode($json);
            if (property_exists($parsed,'data'))
            {
                $data = $parsed->{'data'};
                if (property_exists($data,'org') and property_exists($data->{'org'},'name')) 
                {
                    return $data->{'org'}->{'name'};
                }
                if (property_exists($data,'name'))
                {
                    return $data->{'name'};
                }
            }
        }
        return "";
    }

}
