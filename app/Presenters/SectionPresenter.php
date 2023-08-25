<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model;
use Nette;
use Nette\Application\UI\Form;


class SectionPresenter extends Nette\Application\UI\Presenter
{
	/** @var Model\SectionRepository */
	private $sections;


	public function __construct(Model\SectionRepository $sections)
	{
		$this->sections = $sections;
	}


	protected function startup(): void
	{
		parent::startup();

		if (!$this->getUser()->isLoggedIn()) {
			if ($this->getUser()->getLogoutReason() === Nette\Security\IUserStorage::INACTIVITY) {
				$this->flashMessage('Byl jste kvuli neaktivitě odhlášen. Přihlašte se prosím znovu.');
			}
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
	}


	/********************* view default *********************/


	public function renderDefault(): void
	{
		$this->template->sections = $this->sections->findAll()->order('id');
	}


	/********************* views add & edit *********************/


	public function renderAdd(): void
	{
		$this['sectionForm']['save']->caption = 'Přidat';
	}


	public function renderEdit(int $id): void
	{
		$form = $this['sectionForm'];
		if (!$form->isSubmitted()) {
			$section = $this->sections->findById($id);
			if (!$section) {
				$this->error('Záznam nebyl nalezen.');
			}
			$form->setDefaults($section);
		}
	}


	/********************* view delete *********************/


	public function renderDelete(int $id): void
	{
		$this->template->section = $this->sectionss->findById($id);
		if (!$this->template->section) {
			$this->error('Record not found');
		}
	}


	/********************* component factories *********************/


	/**
	 * Edit form factory.
	 */
	protected function createComponentSectionForm(): Form
	{
		$form = new Form;
		$form->addText('title', 'Název:')
			->setRequired('Prosím zadejte název.');

		$form->addInteger('sort', 'Řazení:')
			->setRequired('Prosím číslo řazení.');
		
		$form->addSubmit('save', 'Uložit')
			->setHtmlAttribute('class', 'default')
			->onClick[] = [$this, 'sectionFormSucceeded'];

		$form->addSubmit('cancel', 'Zpět')
			->setValidationScope([])
			->onClick[] = [$this, 'formCancelled'];

		return $form;
	}


	public function sectionFormSucceeded(Nette\Forms\Controls\SubmitButton $button): void
	{
		$values = $button->getForm()->getValues();
		$id = (int) $this->getParameter('id');
		if ($id) {
			$this->sections->findById($id)->update($values);
			$this->flashMessage('Sekce byla aktualizovaná.');
		} else {
			$this->sections->insert($values);
			$this->flashMessage('Sekce byla přidaná.');
		}
		$this->redirect('default');
	}


	/**
	 * Delete form factory.
	 */
	protected function createComponentDeleteForm(): Form
	{
		$form = new Form;
		$form->addSubmit('cancel', 'Zpět')
			->onClick[] = [$this, 'formCancelled'];

		$form->addSubmit('delete', 'Smazat')
			->setHtmlAttribute('class', 'default')
			->onClick[] = [$this, 'deleteFormSucceeded'];

		return $form;
	}


	public function deleteFormSucceeded(): void
	{
		$this->sections->findById((int) $this->getParameter('id'))->delete();
		$this->flashMessage('Položka byla smazaná.');
		$this->redirect('default');
	}


	public function formCancelled(): void
	{
		$this->redirect('default');
	}
}
