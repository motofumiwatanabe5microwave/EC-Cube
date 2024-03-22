<?php
namespace Customize\Form\Type\Admin;

use Eccube\Common\EccubeConfig;
use Eccube\Form\Type\PhoneNumberType;
use Eccube\Form\Type\Admin\CustomerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractTypeExtension;

/**
 * 会員情報入力Form カスタマイズクラス
 *
 * @category   admin
 * @author     m.watanabe
 * @version    1.0.0
 */
class CustomerCustomizeType extends AbstractTypeExtension
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * CustomerType constructor.
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
            'required' => true,
        ])->add('phone_number02', PhoneNumberType::class, [
            'required' => true,
        ])->add('phone_number03', PhoneNumberType::class, [
            'required' => true,
        ])
        ->remove('phone_number');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
         return CustomerType::class;
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        yield CustomerType::class;
    }
}
