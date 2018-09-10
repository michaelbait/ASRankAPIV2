<?php

namespace App\Controller;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AsCoreController extends Controller{
    /**
     * @Route("/as_core/{asn}", name="as_core")
     * @Route("/as_core/{asn}/{asn2}")
     * @Route("/as_core/");
     * @Route("/as_core")
     */

    public function asn_core($asn = NULL,$asn2 = NULL){

        $process = Null;
        if (NULL != $asn) {

            $filename = tempnam("/tmp","as-rank-as-core_svg");
            $command = array("/usr/bin/env", "python3", "../as-core-viz/as-core-graph.py", 
                "-u", getenv('RESTFUL_DATABASE_URL'), "-f", "SVG","-o", $filename);
            if (NULL != $asn2) {
                array_push($command, "-s", $asn+","+$asn2);
            } elseif (1 == preg_match("/^\d+$/", $asn)) {
                array_push($command, "-a", $asn);
            } else {
                array_push($command, "-O", $asn);
            }
            $process = new Process($command);
            $process->run();

            // executes after the command finishes
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
            $response = new Response(file_get_contents($filename));
            unlink($filename);
            $response->headers->set('Content-Type', 'image/svg+xml');

            return $response;
        }
        return new Response("error");
    }
}
