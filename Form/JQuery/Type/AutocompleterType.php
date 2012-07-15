<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Olivier Chauvel <olivier@generation-multiple.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Genemu\Bundle\FormBundle\Form\JQuery\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormViewInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Genemu\Bundle\FormBundle\Form\Core\ChoiceList\AjaxSimpleChoiceList;
use Genemu\Bundle\FormBundle\Form\Core\DataTransformer\ChoiceToJsonTransformer;

/**
 * @author Olivier Chauvel <olivier@generation-multiple.com>
 */
class AutocompleterType extends AbstractType
{
    private $widget;

    public function __construct($widget)
    {
        $this->widget = $widget;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(new ChoiceToJsonTransformer(
            $options['choice_list'],
            $options['ajax'],
            $this->widget,
            $options['multiple']
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormViewInterface $view, FormInterface $form, array $options)
    {
        $datas = json_decode($form->getViewData(), true);
        $value = '';

        if (!empty($datas)) {
            if ($options['multiple']) {
                foreach ($datas as $data) {
                    $value .= $data['label'] . ', ';
                }
            } else {
                $value = $datas['label'];
            }
        }

        $choices = array();

        foreach ($view->getVar('choices') as $choice) {
            $choices[] = array(
                'value' => $choice->getValue(),
                'label' => $choice->getLabel()
            );
        }

        $view
            ->setVar('choices', $choices)
            ->setVar('autocompleter_value', $value)
            ->setVar('route_name', $options['route_name'])
            ->setVar('free_values', $options['free_values'])
            ->setVar('full_block_name', 'genemu_jqueryautocompleter')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $widget = $this->widget;

        $resolver->setDefaults(array(
            'route_name' => null,
            'ajax' => function (Options $options, $previousValue) {
                if (!empty($options['route_name']) || $options['free_values']) {
                    return true;
                }

                return false;
            },
            'choice_list' => function (Options $options, $previousValue) use ($widget) {
                if (!in_array($widget, array('entity', 'document', 'model'))) {
                    return new AjaxSimpleChoiceList($options['choices'], $options['ajax']);
                }

                return $previousValue;
            },
            'freeValues' => false,
            'free_values' => function (Options $options, $previousValue) {
                if ($options['multiple']) {
                    return false;
                }

                return $options['freeValues'] ?: $previousValue;
            }
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        if (in_array($this->widget, array('entity', 'document', 'model'), true)) {
            return 'genemu_ajax' . $this->widget;
        }

        return $this->widget;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'genemu_jqueryautocompleter_' . $this->widget;
    }
}
