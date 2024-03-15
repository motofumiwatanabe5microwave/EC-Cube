<?php
namespace Customize\Service\Extension;

use Eccube\Entity\Customer;
use Eccube\Entity\Shipping;
use Eccube\Service\OrderHelper;

class OrderHelperExtension extends OrderHelper
{
    /**
     * セッションに保持されている非会員情報を取得する.
     * 非会員購入時に入力されたお客様情報を返す.
     *
     * @param string $session_key
     *
     * @return Customer|null
     */
    public function getNonMember($session_key = self::SESSION_NON_MEMBER)
    {
        $data = $this->session->get($session_key);
        if (empty($data)) {
            return null;
        }
        $Customer = new Customer();
        $Customer
            ->setName01($data['name01'])
            ->setName02($data['name02'])
            ->setKana01($data['kana01'])
            ->setKana02($data['kana02'])
            ->setCompanyName($data['company_name'])
            ->setEmail($data['email'])
            // feature-002 電話番号設定変更
            ->setPhonenumber($data['phone_number'])
            ->setPostalcode($data['postal_code'])
            ->setAddr01($data['addr01'])
            ->setAddr02($data['addr02']);

        if (!empty($data['pref'])) {
            $Pref = $this->prefRepository->find($data['pref']);
            $Customer->setPref($Pref);
        }

        return $Customer;
    }

    /**
     * @param Customer $Customer
     *
     * @return Shipping
     */
    protected function createShippingFromCustomer(Customer $Customer)
    {
        $Shipping = new Shipping();
        $Shipping
            ->setName01($Customer->getName01())
            ->setName02($Customer->getName02())
            ->setKana01($Customer->getKana01())
            ->setKana02($Customer->getKana02())
            ->setCompanyName($Customer->getCompanyName())
            ->setPhoneNumber($Customer->getPhoneNumber())
            ->setPostalCode($Customer->getPostalCode())
            ->setPref($Customer->getPref())
            ->setAddr01($Customer->getAddr01())
            ->setAddr02($Customer->getAddr02());

            return $Shipping;
    }
}
