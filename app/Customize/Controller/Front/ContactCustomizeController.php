<?php
namespace Customize\Controller\Front;

use Eccube\Entity\Customer;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\Front\ContactType;
use Eccube\Repository\PageRepository;
use Eccube\Service\MailService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * お問い合わせ カスタマイズコントローラー
 *
 * @category   Front
 * @author     m.watanabe
 * @version    1.0.0
 */
class ContactCustomizeController extends AbstractFrontCustomizeController
{
    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var PageRepository
     */
    private $pageRepository;

    /**
     * ContactController constructor.
     *
     * @param MailService $mailService
     * @param PageRepository $pageRepository
     */
    public function __construct(
        MailService $mailService,
        PageRepository $pageRepository)
    {
        $this->mailService = $mailService;
        $this->pageRepository = $pageRepository;
    }

    /**
     * お問い合わせ画面.
     *
     * @Route("/contact", name="contact", methods={"GET", "POST"})
     * @Route("/contact", name="contact_confirm", methods={"GET", "POST"})
     * @Template("Contact/index.twig")
     */
    public function index(Request $request)
    {
        $builder = $this->formFactory->createBuilder(ContactType::class);

        if ($this->isGranted('ROLE_USER')) {
            /** @var Customer $user */
            $user = $this->getUser();
            $builder->setData(
                [
                    'name01' => $user->getName01(),
                    'name02' => $user->getName02(),
                    'kana01' => $user->getKana01(),
                    'kana02' => $user->getKana02(),
                    'postal_code' => $user->getPostalCode(),
                    'pref' => $user->getPref(),
                    'addr01' => $user->getAddr01(),
                    'addr02' => $user->getAddr02(),
                    // feature-002 電話番号設定変更
                    'phone_number01' => $user->getPhoneNumber01(),
                    'phone_number02' => $user->getPhoneNumber02(),
                    'phone_number03' => $user->getPhoneNumber03(),
                    'email' => $user->getEmail(),
                ]
            );
        }

        // FRONT_CONTACT_INDEX_INITIALIZE
        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch($event, EccubeEvents::FRONT_CONTACT_INDEX_INITIALIZE);

        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            switch ($request->get('mode')) {
                case 'confirm':
                    return $this->render('Contact/confirm.twig', [
                        'form' => $form->createView(),
                        'Page' => $this->pageRepository->getPageByRoute('contact_confirm'),
                    ]);

                case 'complete':
                    $data = $form->getData();

                    $event = new EventArgs(
                        [
                            'form' => $form,
                            'data' => $data,
                        ],
                        $request
                    );
                    $this->eventDispatcher->dispatch($event, EccubeEvents::FRONT_CONTACT_INDEX_COMPLETE);

                    $data = $event->getArgument('data');

                    // メール送信
                    $this->mailService->sendContactMail($data);

                    return $this->redirect($this->generateUrl('contact_complete'));
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
