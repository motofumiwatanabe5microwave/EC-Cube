<?php
namespace Customize\Controller\Front;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;

/**
 * ご利用案内 カスタマイズコントローラー
 *
 * @category   Front
 * @author     m.watanabe
 * @version    1.0.0
 */
class HelpCustomizeController extends AbstractFrontCustomizeController
{
    /**
     * HelpController constructor.
     */
    public function __construct()
    {
    }

    /**
     * ご利用ガイド.
     *
     * @Route("/help/faq", name="help_faq", methods={"GET"})
     * @Template("Help/faq.twig")
     */
    public function faq()
    {
        return [];
    }
}
