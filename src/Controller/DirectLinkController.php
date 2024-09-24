<?php

namespace App\Controller;

use App\Service\OpenURLService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Try to resolve a direct link to an item
 *
 * Sometimes we want to link directly to an online resource rather than to
 * its record in Primo. This controller tries to do that. If it can't, it
 * links to the Primo record.
 */
class DirectLinkController extends AbstractController
{
    private OpenURLService $openurl;

    public function __construct(OpenURLService $openurl)
    {
        $this->openurl = $openurl;
    }

    public function link(string $mms): RedirectResponse
    {
        $link = $this->openurl->lookup($mms);
        return $this->redirect($link);
    }
}
