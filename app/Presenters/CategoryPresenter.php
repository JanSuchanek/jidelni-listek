<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model;
use Nette;
use Nette\Application\UI\Form;


class CategoryPresenter extends Nette\Application\UI\Presenter
{
	/** @var Model\CategoryRepository */
	private $categories;

	/** @var Model\SectionRepository */
	private $sections;

	public function __construct(Model\CategoryRepository $categories, Model\SectionRepository $sections)
	{
		$this->categories = $categories;
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
		$this->template->categories = $this->categories->findAll()->order('section_id, sort');
	}


	/********************* views add & edit *********************/


	public function renderAdd(): void
	{
		$this['categoryForm']['save']->caption = 'Přidat';
	}


	public function renderEdit(int $id): void
	{
		$form = $this['categoryForm'];
		if (!$form->isSubmitted()) {
			$category = $this->categories->findById($id);
			if (!$category) {
				$this->error('Záznam nebyl nalezen.');
			}
			$form->setDefaults($category);
		}
	}


	/********************* view delete *********************/


	public function renderDelete(int $id): void
	{
		$this->template->category = $this->categories->findById($id);
		if (!$this->template->category) {
			$this->error('Record not found');
		}
	}


	/********************* component factories *********************/


	/**
	 * Edit form factory.
	 */
	protected function createComponentCategoryForm(): Form
	{
		$form = new Form;

		$sections = $this->sections->findAll()->order('id')->fetchPairs('id', 'title');
		$form->addSelect('section_id', 'Sekce:', $sections);

		$form->addText('title', 'Název:')
			->setRequired('Prosím zadejte název.');

		$form->addInteger('sort', 'Řazení:')
			->setRequired('Prosím číslo řazení.');
		
		$form->addSubmit('save', 'Uložit')
			->setHtmlAttribute('class', 'default')
			->onClick[] = [$this, 'categoryFormSucceeded'];

		$form->addSubmit('cancel', 'Zpět')
			->setValidationScope([])
			->onClick[] = [$this, 'formCancelled'];

		return $form;
	}


	public function categoryFormSucceeded(Nette\Forms\Controls\SubmitButton $button): void
	{
		$values = $button->getForm()->getValues();
		$id = (int) $this->getParameter('id');
		if ($id) {
			$this->categories->findById($id)->update($values);
			$this->flashMessage('Kategorie byla aktualizovaná.');
		} else {
			$this->categories->insert($values);
			$this->flashMessage('Kategorie byla přidaná.');
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
		$this->categories->findById((int) $this->getParameter('id'))->delete();
		$this->flashMessage('Položka byla smazaná.');
		$this->redirect('default');
	}


	public function formCancelled(): void
	{
		$this->redirect('default');
	}
}
