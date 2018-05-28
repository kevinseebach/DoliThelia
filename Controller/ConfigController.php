<?php
namespace Dolithelia\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Form\Exception\FormValidationException;
use Thelia\Tools\URL;
use Thelia\Model\ConfigQuery;
use Dolithelia\Form\ConfigForm;
use Dolithelia\Dolithelia;

class ConfigController extends BaseAdminController
{
    public function saveAction()
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, ['Dolithelia'], AccessManager::UPDATE)) {
            return $response;
        }
        $form = new ConfigForm($this->getRequest());
        $errorMessage = null;
        $response = null;
        try {
            $configForm = $this->validateForm($form);
            ConfigQuery::write('Dolithelia_api_key', $configForm->get('api_key')->getData(), 1, 1);
            ConfigQuery::write('Dolithelia_base_url', $configForm->get('base_url')->getData(), 1, 1);
            $response = RedirectResponse::create(URL::getInstance()->absoluteUrl('/admin/module/Dolithelia'));
        } catch (FormValidationException $e) {
            $errorMessage = $e->getMessage();
        }
        if (null !== $errorMessage) {
            $this->setupFormErrorContext(
                'Dolithelia config fail',
                $errorMessage,
                $form
            );
            $response = $this->render(
                "module-configure",
                [
                    'module_code' => 'Dolithelia'
                ]
            );
        }
        return $response;
    }
}
