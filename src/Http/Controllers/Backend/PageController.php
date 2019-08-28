<?php

namespace Yasha\Backend\Http\Controllers\Backend;

use Yasha\Backend\Traits\PageTemplates;
use Backpack\CRUD\app\Http\Controllers\CrudController;
// VALIDATION: change the requests to match your own file names if you need form validation
use Illuminate\Http\Request as StoreRequest;
use Illuminate\Http\Request as UpdateRequest;

class PageController extends CrudController
{
    use PageTemplates;

    public function setup($template_name = false)
    {
        $modelClass = config('backpack.pagemanager.page_model_class', 'Yasha\Backend\Models\Page');

        $this->crud->setModel($modelClass);
        $this->crud->setRoute(config('backpack.base.route_prefix').'/page');

        $this->crud->setEntityNameStrings(__('yasha/backend::pagemanager.page'), __('yasha/backend::pagemanager.pages'));

        $this->crud->addColumn([
                                'name' => 'name',
                                'label' => __('yasha/backend::pagemanager.name'),
                                ]);
        $this->crud->addColumn([
                                'name' => 'template',
                                'label' => __('yasha/backend::pagemanager.template'),
                                'type' => 'model_function',
                                'function_name' => 'getTemplateName',
                                ]);
        $this->crud->addColumn([
                                'name' => 'slug',
                                'label' => __('yasha/backend::pagemanager.slug'),
                                ]);

        // In PageManager,
        // - default fields, that all templates are using, are set using 
        $this->addDefaultPageFields();
        // - template-specific fields are set per-template, in the PageTemplates trait;

        $this->crud->addButtonFromModelFunction('line', 'open', 'getOpenButton', 'beginning');
    }

    // Overwrites the CrudController create() method to add template usage.
    public function create($template = false)
    {
        $template = request('template');

        $this->addDefaultPageFields($template);
        $this->useTemplate($template);

        return parent::create($template);
    }

    // Overwrites the CrudController store() method to add template usage.
    public function store(StoreRequest $request)
    {
        $this->addDefaultPageFields(\Request::input('template'));
        $this->useTemplate(\Request::input('template'));
        
        return parent::storeCrud();
    }

    // Overwrites the CrudController edit() method to add template usage.
    public function edit($id, $template = false)
    {
        $template = request('template');

        // if the template in the GET parameter is missing, figure it out from the db
        if ($template == false) {
            $model = $this->crud->model;
            $this->data['entry'] = $model::findOrFail($id);
            $template = $this->data['entry']->template;
        }

        $this->addDefaultPageFields($template);
        $this->useTemplate($template);

        return parent::edit($id);
    }

    // Overwrites the CrudController update() method to add template usage.
    public function update(UpdateRequest $request)
    {
        $this->addDefaultPageFields(\Request::input('template'));
        $this->useTemplate(\Request::input('template'));

        return parent::updateCrud();
    }

    /**
     * Populate the create/update forms with basic fields, that all pages need.
     *
     * @param string $template The name of the template that should be used in the current form.
     */
    public function addDefaultPageFields($template = false)
    {
        $this->crud->addField([
                                'name' => 'template',
                                'label' => __('yasha/backend::pagemanager.template'),
                                'type' => 'select_page_template',
                                'view_namespace'  => 'yasha-backend::fields',
                                'options' => $this->getTemplatesArray(),
                                'value' => $template,
                                'allows_null' => false,
                                'wrapperAttributes' => [
                                    'class' => 'form-group col-md-6',
                                ],
                            ]);
        $this->crud->addField([
                                'name' => 'name',
                                'label' => __('yasha/backend::pagemanager.page_name'),
                                'type' => 'text',
                                'wrapperAttributes' => [
                                    'class' => 'form-group col-md-6',
                                ],
                                // 'disabled' => 'disabled'
                            ]);
        $this->crud->addField([
                                'name' => 'title',
                                'label' => __('yasha/backend::pagemanager.page_title'),
                                'type' => 'text',
                                // 'disabled' => 'disabled'
                            ]);
        $this->crud->addField([
                                'name' => 'slug',
                                'label' => __('yasha/backend::pagemanager.page_slug'),
                                'type' => 'text',
                                'hint' => __('yasha/backend::pagemanager.page_slug_hint'),
                                // 'disabled' => 'disabled'
                            ]);
    }

    /**
     * Add the fields defined for a specific template.
     *
     * @param  string $template_name The name of the template that should be used in the current form.
     */
    public function useTemplate($template_name = false)
    {
        $templates = $this->getTemplates();

        // set the default template
        if ($template_name == false) {
            $template_name = $templates[0]->name;
        }

        // actually use the template
        if ($template_name) {
            $this->{$template_name}();
        }
    }

    /**
     * Get all defined templates.
     */
    public function getTemplates($template_name = false)
    {
        $templates_array = [];

        $templates_trait = new \ReflectionClass('Yasha\Backend\Traits\PageTemplates');
        $templates = $templates_trait->getMethods(\ReflectionMethod::IS_PRIVATE);

        if (! count($templates)) {
            abort(503, __('yasha/backend::pagemanager.template_not_found'));
        }

        return $templates;
    }

    /**
     * Get all defined template as an array.
     *
     * Used to populate the template dropdown in the create/update forms.
     */
    public function getTemplatesArray()
    {
        $templates = $this->getTemplates();

        foreach ($templates as $template) {
            $templates_array[$template->name] = str_replace('_', ' ', title_case($template->name));
        }

        return $templates_array;
    }
}