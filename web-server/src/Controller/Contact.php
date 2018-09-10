<?php
// src/Controller/ASN.php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class Contact extends Controller
{
    /**
     * @Route("/contact", name="contact")
     */
    public function about()
    {
        return $this->render('contact.html.twig');
    }
}
