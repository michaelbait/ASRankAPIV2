<?php
// src/Controller/ASN.php
namespace App\Controller;

use App\Classes\Org_Information;
use App\Classes\Location;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrgController extends Controller
{
    const PAGE_SIZE = 40;
    /**
     * @Route("/orgs", name="orgs_ranking")
     * @Route("/orgs/ranked", name="orgs_ranked")
     */
    public function orgs(Request $request)
    {
        
        // This captures old requests to the pre symfony version of the code
        //
        $mode0 = $request->query->get('mode0');
        if ($mode0 != null) {
            if ($mode0 == "org-ranking") {
                return $this->redirectToRoute('default', array(), 301);
            } 
            if ($mode0 == "org-info") {
                $asn = $request->query->get('as');
                if ($asn != null) {
                    return $this->redirectToRoute('org_information', array(
                        "org"=>$org
                    ), 301);
                }
            }
        } 
        $params = $request->query->all();
        $page = $request->query->get('page');
        $page_size = $request->query->get('count');
        $sort_type = $request->query->get('sort_type');
        $sort_dir = $request->query->get('sort_dir');

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
        return $this->render('asns/orgs.html.twig', array(
            'page' => $page 
            ,'sort_type' => $sort_type
            ,'sort_dir' => $sort_dir
            ,'page_size' => $page_size
            ,'org' => true,
        ));
    }

    /**
     * @Route("/orgs/{org}", name="org_members")
     * @Route("/orgs/{org}/members")
     * @Route("/orgs/{org}/as-core", name="org_as_core", defaults={"area"="as-core"})
     * @Route("/orgs/by_name/{string}", name="org-search")
     */
    public function org(Request $request, $org="", $area="members")
    {
        /*
        if (!preg_match("/^\d+$/", $org)) {
            return $this->org_search($request, $org);
        } */

        $page = $this->digit_santizer($request->query->get('page'), 1);
        $sort_type = $request->query->get('sort_type');
        $sort_dir = $request->query->get('sort_dir');
        $org_info = new Org_Information($org);
        $org_info->GET_JSON($org);

        $location = new Location("org",$area, $org_info);

        return $this->render('asns/org.html.twig', array(
            'org_info' => $org_info
            ,'page' => $page
            ,'sort_type' => $sort_type
            ,'sort_dir' => $sort_dir
            ,'page_size' => self::PAGE_SIZE
            ,'org' => true
            ,'location' => $location
        ));
    }

    /**
     * @Route("/orgs/", name="orgs_search")
     * @Route("/orgs/by-name")
     * @Route("/orgs/by-name/")
     * @Route("/orgs/by-name/{name}")
     */
    public function org_search(Request $request, $name = NULL)
    {
        $page = $this->digit_santizer($request->query->get('page'), 1);
        if ($name == NULL) {
            $name = $request->query->get('name');
        }
        $type = $request->query->get('type');

        if ($type != NULL && strcmp($type,"go to") == 0) {
            return $this->org($request, $name);
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
        $json = file_get_contents(getenv('RESTFUL_DATABASE_URL').'/orgs/'.$asn.'?populate=1');

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
