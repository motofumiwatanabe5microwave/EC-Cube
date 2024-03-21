<?php
namespace Customize\Form\Type\Admin;

use Eccube\Common\EccubeConfig;
use Eccube\Form\Type\PhoneNumberType;
use Eccube\Form\Type\Admin\SearchCustomerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * 会員検索入力Form カスタマイズクラス
 *
 * @category   admin
 * @author     m.watanabe
 * @version    1.0.0
 */
class SearchCustomerCustomizeType extends AbstractTypeExtension
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * OrderType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // feature-002 電話番号設定変更
        $builder->add('phone_number01', PhoneNumberType::class, [
            'label' => 'admin.common.phone_number',
            'required' => false,
            'constraints' => [
                new Assert\Regex([
                    'pattern' => "/^[\d-]+$/u",
                    'message' => 'form_error.graph_and_hyphen_only',
                ]),
            ],
        ])->add('phone_number02', PhoneNumberType::class, [
            'required' => false,
            'constraints' => [
                new Assert\Regex([
                    'pattern' => "/^[\d-]+$/u",
                    'message' => 'form_error.graph_and_hyphen_only',
                ]),
            ],
        ])->add('phone_number03', PhoneNumberType::class, [
            'required' => false,
            'constraints' => [
                new Assert\Regex([
                    'pattern' => "/^[\d-]+$/u",
                    'message' => 'form_error.graph_and_hyphen_only',
                ]),
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
         return SearchCustomerType::class;
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        yield SearchCustomerType::class;
    }
}
