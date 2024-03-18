<?php
namespace Customize\Controller\Front;

use Eccube\Controller\AbstractShoppingController;

/**
 * Front用 Shopping継承コントローラー
 *
 * @category   Front
 * @author     m.watanabe
 * @version    1.0.0
 */
class AbstractShoppingCustomizeController extends AbstractShoppingController
{
    /**
     * ハッシュ用Salt存在確認(旧パスワード形式確認)
     * feature-001 ログイン時旧パスワード再設定
     *
     * @return boolean
     */
    protected function isCustomerSalt()
    {
        $Customer = $this->getUser();

        if (!is_null($Customer) && is_null($Customer->getSalt())) {
            return false;
        }

        return true;
    }

    /**
     * パスワード再設定遷移
     * feature-001 ログイン時旧パスワード再設定
     *
     * @return RedirectResponse
     */
    protected function setPasswordResetting()
    {
        return $this->redirect($this->generateUrl('mypage_resetting'), 302);
    }
}
