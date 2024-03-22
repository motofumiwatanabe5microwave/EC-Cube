<?php
namespace Customize\Controller\Front;

use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Validator\Email;
use Eccube\Repository\Master\PrefRepository;
use Eccube\Service\CartService;
use Eccube\Service\OrderHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * 未登録者購入 カスタマイズコントローラー
 *
 * @category   Front
 * @author     m.watanabe
 * @version    1.0.0
 */
class NonMemberShoppingCustomizeController extends AbstractFrontCustomizeController
{
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var PrefRepository
     */
    protected $prefRepository;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * NonMemberShoppingController constructor.
     *
     * @param ValidatorInterface $validator
     * @param PrefRepository $prefRepository
     * @param OrderHelper $orderHelper
     * @param CartService $cartService
     */
    public function __construct(
        ValidatorInterface $validator,
        PrefRepository $prefRepository,
        OrderHelper $orderHelper,
        CartService $cartService
    ) {
        $this->validator = $validator;
        $this->prefRepository = $prefRepository;
        $this->orderHelper = $orderHelper;
        $this->cartService = $cartService;
    }

    /**
     * お客様情報の変更(非会員)
     *
     * @Route("/shopping/customer", name="shopping_customer", methods={"POST"})
     */
    public function customer(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            return $this->json(['status' => 'NG'], 400);
        }
        $this->isTokenValid();
        try {
            log_info('非会員お客様情報変更処理開始');
            $data = $request->request->all();
            // 入力チェック
            $errors = $this->customerValidation($data);
            foreach ($errors as $error) {
                if ($error->count() != 0) {
                    log_info('非会員お客様情報変更入力チェックエラー');

                    return $this->json(['status' => 'NG'], 400);
                }
            }
            $pref = $this->prefRepository->findOneBy(['name' => $data['customer_pref']]);
            if (!$pref) {
                log_info('非会員お客様情報変更入力チェックエラー');

                return $this->json(['status' => 'NG'], 400);
            }
            $preOrderId = $this->cartService->getPreOrderId();
            $Order = $this->orderHelper->getPurchaseProcessingOrder($preOrderId);
            if (!$Order) {
                log_info('受注が存在しません');
                $this->addError('front.shopping.order_error');

                return $this->redirectToRoute('shopping_error');
            }
            $Order
                ->setName01($data['customer_name01'])
                ->setName02($data['customer_name02'])
                ->setKana01($data['customer_kana01'])
                ->setKana02($data['customer_kana02'])
                ->setCompanyName($data['customer_company_name'])
                // feature-002 電話番号設定変更
                ->setPhoneNumber01($data['customer_phone_number01'])
                ->setPhoneNumber02($data['customer_phone_number02'])
                ->setPhoneNumber03($data['customer_phone_number03'])
                ->setPostalCode($data['customer_postal_code'])
                ->setPref($pref)
                ->setAddr01($data['customer_addr01'])
                ->setAddr02($data['customer_addr02'])
                ->setEmail($data['customer_email']);

            $this->entityManager->flush();

            $this->session->set(OrderHelper::SESSION_NON_MEMBER, [
                'name01' => $data['customer_name01'],
                'name02' => $data['customer_name02'],
                'kana01' => $data['customer_kana01'],
                'kana02' => $data['customer_kana02'],
                'company_name' => $data['customer_company_name'],
                // feature-002 電話番号設定変更
                'phone_number01' => $data['customer_phone_number01'],
                'phone_number02' => $data['customer_phone_number02'],
                'phone_number03' => $data['customer_phone_number03'],
                'postal_code' => $data['customer_postal_code'],
                'pref' => $pref,
                'addr01' => $data['customer_addr01'],
                'addr02' => $data['customer_addr02'],
                'email' => $data['customer_email'],
            ]);

            $event = new EventArgs(
                [
                    'Order' => $Order,
                    'data' => $data,
                ],
                $request
            );
            $this->eventDispatcher->dispatch($event, EccubeEvents::FRONT_SHOPPING_CUSTOMER_INITIALIZE);
            log_info('非会員お客様情報変更処理完了', [$Order->getId()]);
            $message = ['status' => 'OK', 'kana01' => $data['customer_kana01'], 'kana02' => $data['customer_kana02']];

            $response = $this->json($message);
        } catch (\Exception $e) {
            log_error('予期しないエラー', [$e->getMessage()]);

            $response = $this->json(['status' => 'NG'], 500);
        }

        return $response;
    }

    /**
     * 非会員でのお客様情報変更時の入力チェック
     *
     * @param array $data リクエストパラメータ
     *
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface[]
     */
    protected function customerValidation(array &$data)
    {
        // 入力チェック
        $errors = [];

        $errors[] = $this->validator->validate(
            $data['customer_name01'],
            [
                new Assert\NotBlank(),
                new Assert\Length(['max' => $this->eccubeConfig['eccube_name_len']]),
                new Assert\Regex(
                    ['pattern' => '/^[^\s ]+$/u', 'message' => 'form_error.not_contain_spaces']
                ),
            ]
        );

        $errors[] = $this->validator->validate(
            $data['customer_name02'],
            [
                new Assert\NotBlank(),
                new Assert\Length(['max' => $this->eccubeConfig['eccube_name_len']]),
                new Assert\Regex(
                    ['pattern' => '/^[^\s ]+$/u', 'message' => 'form_error.not_contain_spaces']
                ),
            ]
        );

        $data['customer_kana01'] = mb_convert_kana($data['customer_kana01'], 'CV', 'utf-8');
        $errors[] = $this->validator->validate(
            $data['customer_kana01'],
            [
                new Assert\NotBlank(),
                new Assert\Length(['max' => $this->eccubeConfig['eccube_kana_len']]),
                new Assert\Regex(['pattern' => '/^[ァ-ヶｦ-ﾟー]+$/u']),
            ]
        );
        $data['customer_kana02'] = mb_convert_kana($data['customer_kana02'], 'CV', 'utf-8');
        $errors[] = $this->validator->validate(
            $data['customer_kana02'],
            [
                new Assert\NotBlank(),
                new Assert\Length(['max' => $this->eccubeConfig['eccube_kana_len']]),
                new Assert\Regex(['pattern' => '/^[ァ-ヶｦ-ﾟー]+$/u']),
            ]);

        $errors[] = $this->validator->validate(
            $data['customer_company_name'],
            [
                new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
            ]
        );

        // feature-002 電話番号設定変更
        $errors[] = $this->validator->validate(
            $data['customer_phone_number01'],
            [
                new Assert\NotBlank(),
                new Assert\Type(['type' => 'digit', 'message' => 'form_error.numeric_only']),
                new Assert\Length(
                    ['max' => $this->eccubeConfig['eccube_tel_len_max']]
                ),
            ]
        );
        $errors[] = $this->validator->validate(
            $data['customer_phone_number02'],
            [
                new Assert\NotBlank(),
                new Assert\Type(['type' => 'digit', 'message' => 'form_error.numeric_only']),
                new Assert\Length(
                    ['max' => $this->eccubeConfig['eccube_tel_len_max']]
                ),
            ]
        );
        $errors[] = $this->validator->validate(
            $data['customer_phone_number03'],
            [
                new Assert\NotBlank(),
                new Assert\Type(['type' => 'digit', 'message' => 'form_error.numeric_only']),
                new Assert\Length(
                    ['max' => $this->eccubeConfig['eccube_tel_len_max']]
                ),
            ]
        );

        $errors[] = $this->validator->validate(
            $data['customer_postal_code'],
            [
                new Assert\NotBlank(),
                new Assert\Type(['type' => 'digit', 'message' => 'form_error.numeric_only']),
                new Assert\Length(
                    ['max' => $this->eccubeConfig['eccube_postal_code']]
                ),
            ]
        );

        $errors[] = $this->validator->validate(
            $data['customer_addr01'],
            [
                new Assert\NotBlank(),
                new Assert\Length(['max' => $this->eccubeConfig['eccube_address1_len']]),
            ]
        );

        $errors[] = $this->validator->validate(
            $data['customer_addr02'],
            [
                new Assert\NotBlank(),
                new Assert\Length(['max' => $this->eccubeConfig['eccube_address2_len']]),
            ]
        );

        $errors[] = $this->validator->validate(
            $data['customer_email'],
            [
                new Assert\NotBlank(),
                new Email(null, null, $this->eccubeConfig['eccube_rfc_email_check'] ? 'strict' : null),
            ]
        );

        return $errors;
    }
}
