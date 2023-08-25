<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model;
use Nette;
use Nette\Application\UI\Form;


class MenuPresenter extends Nette\Application\UI\Presenter
{
	/** @var Model\FoodRepository */
	private $foods;
	/** @var Model\CategoryRepository */
	private $categories;

	/** @var Model\SectionRepository */
	private $sections;

	/** @var array */
	private $days = [
		0 => '',
		1 => 'Pondělí',
		2 => 'Úterý',
		3 => 'Středa',
		4 => 'Čtvrtek',
		5 => 'Pátek',
		6 => 'Sobota',
		7 => 'Neděle',
	];

	public function __construct(Model\FoodRepository $foods, Model\CategoryRepository $categories, Model\SectionRepository $sections)
	{
		$this->foods = $foods;
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
		$this->template->sections = $this->sections->findAll()->fetchPairs('id', 'title');
		$this->template->categories = $this->categories->findAll()->order('sort')->fetchPairs('id', 'title');
		$this->template->days = $this->days;

		$foods = [];

		foreach($this->foods->findAll()->order('category_id, sort') as $food){
			if(!isset($foods[$food->ref("categories","category_id")->section_id][$food->category_id])){
				$foods[$food->ref("categories","category_id")->section_id][$food->category_id] = [];
			}

			$foods[$food->ref("categories","category_id")->section_id][$food->category_id][$food->id] = $food->toArray();
		}


		$this->template->foods = $foods;
	}


	/********************* view lunch *********************/


	public function renderLunch(): void
	{
		$ids = $this->categories->findAll()->where("section_id",1)->fetchPairs('id', 'id');
		$this->template->foods = $this->foods->findAll()->where("category_id", $ids)
			->order('category_id, sort');
	}

	/********************* views add & edit *********************/


	public function renderAdd(): void
	{
		$this['foodForm']['save']->caption = 'Přidat';
	}


	public function renderEdit(int $id): void
	{
		$form = $this['foodForm'];
		if (!$form->isSubmitted()) {
			$food = $this->foods->findById($id);
			if (!$food) {
				$this->error('Záznam nebyl nalezen.');
			}
			$form->setDefaults($food);
		}
	}


	/********************* view delete *********************/


	public function renderDelete(int $id): void
	{
		$this->template->food = $this->foods->findById($id);
		if (!$this->template->food) {
			$this->error('Záznam nebyl nalezen.');
		}
	}


	/********************* component factories *********************/


	/**
	 * Edit form factory.
	 */
	protected function createComponentFoodForm(): Form
	{
		$form = new Form;
		$categories = $this->categories->findAll()->order('sort')->fetchPairs('id', 'title');
		$form->addSelect('category_id', 'Země:', $categories);

		$form->addSelect('day', 'Den:', $this->days)
			->setRequired('Prosím zadejte den.');

		$form->addInteger('sort', 'Řazení:')
			->setRequired('Prosím číslo řazení.');

		$form->addText('title', 'Název:')
			->setRequired('Prosím zadejte název.');
		
		$form->addText('weight', 'Porce:');

		$form->addInteger('price', 'Cena:')
			->setRequired('Prosím zadejte cenu.');

		$form->addInteger('delivery', 'Doprava:')
			->setRequired('Prosím zadejte dopravy.');

		$form->addSubmit('save', 'Uložit')
			->setHtmlAttribute('class', 'default')
			->onClick[] = [$this, 'foodFormSucceeded'];

		$form->addSubmit('cancel', 'Zpět')
			->setValidationScope([])
			->onClick[] = [$this, 'formCancelled'];

		return $form;
	}


	public function foodFormSucceeded(Nette\Forms\Controls\SubmitButton $button): void
	{
		$values = $button->getForm()->getValues();
		$id = (int) $this->getParameter('id');
		if ($id) {
			$this->foods->findById($id)->update($values);
			$this->flashMessage('Jídlo bylo aktualizováno.');
		} else {
			$this->foods->insert($values);
			$this->flashMessage('Jídlo bylo přidáno.');
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
		$this->foods->findById((int) $this->getParameter('id'))->delete();
		$this->flashMessage('Položka byla smazaná.');
		$this->redirect('default');
	}


	public function formCancelled(): void
	{
		$this->redirect('default');
	}
}
