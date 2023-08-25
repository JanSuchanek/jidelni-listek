<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI;


class SignPresenter extends Nette\Application\UI\Presenter
{
	/** @persistent */
	public $backlink = '';


	/**
	 * Sign-in form factory.
	 */
	protected function createComponentSignInForm(): UI\Form
	{
		$form = new UI\Form;
		$form->addText('username', 'Uživatel:')
			->setRequired('Zadejte prosím uživatelské jméno.');

		$form->addPassword('password', 'Heslo:')
			->setRequired('Zadejte prosím heslo.');

		$form->addSubmit('send', 'Přihlásit se');

		$form->onSuccess[] = [$this, 'signInFormSucceeded'];
		return $form;
	}


	public function signInFormSucceeded(UI\Form $form, \stdClass $values): void
	{
		try {
			$this->getUser()->login($values->username, $values->password);

		} catch (Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
			return;
		}

		$this->restoreRequest($this->backlink);
		$this->redirect('Menu:');
	}


	public function actionOut(): void
	{
		$this->getUser()->logout();
		$this->flashMessage('Byl jste odhlášen.');
		$this->redirect('in');
	}
}
