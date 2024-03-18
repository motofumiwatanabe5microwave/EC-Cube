<?php
namespace Customize\Form\Type\Front;

use Eccube\Common\EccubeConfig;
use Eccube\Form\Type\PhoneNumberType;
use Eccube\Form\Type\Front\ContactType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractTypeExtension;

class ContactCustomizeType extends AbstractTypeExtension
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
            'required' => false,
        ])->add('phone_number02', PhoneNumberType::class, [
            'required' => false,
        ])->add('phone_number03', PhoneNumberType::class, [
            'required' => false,
        ])
        ->remove('phone_number');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
         return ContactType::class;
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        yield ContactType::class;
    }
}
