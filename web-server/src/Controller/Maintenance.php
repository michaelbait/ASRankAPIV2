<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class Maintenance extends Controller
{
    /**
     * @Route("/maintenance", name="maintenance")
     */
    public function maintenance()
    {
        return $this->render('maintenance.html.twig');
    }
}
