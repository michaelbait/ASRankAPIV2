<?php
// src/Controller/ASN.php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class FAQ extends Controller
{
    /**
     * @Route("/faq", name="faq")
     */
    public function about()
    {
        return $this->render('faq.html.twig');
    }
}
