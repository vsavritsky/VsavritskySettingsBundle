<?php

namespace Vsavritsky\SettingsBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\Type\ModelListType;
use Sonata\AdminBundle\Show\ShowMapper;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Vsavritsky\SettingsBundle\DBAL\SettingsType;
use Vsavritsky\SettingsBundle\Entity\Settings;
use Vsavritsky\SettingsBundle\Entity\Category;

class SettingsAdmin extends AbstractAdmin
{
    public function configureListFields(ListMapper $listMapper)
    {
        $useCategoryComment = $this->getConfigurationPool()->getContainer()
            ->getParameter('vsavritsky_settings.use_category_comment');

        $listMapper
            ->addIdentifier('name')
            ->add('category', null, array(
                'associated_property' => function(Category $cat) use ($useCategoryComment) {
                    return $useCategoryComment && $cat->getComment() ? $cat->getComment() : $cat->getName();
                },
                'sortable' => true,
                'sort_field_mapping' => array('fieldName' => 'name'),
                'sort_parent_association_mappings' => array(array('fieldName' => 'category'))
            ))
            ->add('type', ChoiceType::class, array('choices' => SettingsType::getReadableValues(), 'catalogue' => 'messages'))
            ->add('value', null, array('template' => 'VsavritskySettingsBundle:Admin:list_value.html.twig'))
            ->add('comment')
        ;
    }

    public function configureFormFields(FormMapper $formMapper)
    {
        $valueType = $this->isNewForm()
            ? 'Vsavritsky\SettingsBundle\Form\Type\SettingValueType'
            : 'setting_value';
        $formMapper
            ->add('name')
            ->add('category', ModelListType::class)
            ->add('type', ChoiceType::class, array(
                'choices' => SettingsType::getChoices(),
                'attr' => array('data-sonata-select2'=>'false'),
            ))
            ->add('value', $valueType)
            ->add('comment')
        ;
    }

    public function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $useCategoryComment = $this->getConfigurationPool()->getContainer()
            ->getParameter('vsavritsky_settings.use_category_comment');

        $categoryOptions = $this->isNewForm()
            ? array(
                'choice_label' => function (Category $cat) use ($useCategoryComment) {
                    return $useCategoryComment && $cat->getComment() ? $cat->getComment() : $cat->getName();
                },
            ) : array();
        $datagridMapper
            ->add('category', null, array(), null, $categoryOptions)
            ->add('name')
            ->add('type', null, array(), ChoiceType::class, array('choices' => SettingsType::getChoices()))
        ;
    }

    public function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('name')
            ->add('type')
            ->add('value')
            ->add('comment')
        ;
    }

    /**
     * @param Settings $object
     */
    public function postPersist($object)
    {
        $this->clearCache($object);
    }

    /**
     * @param Settings $object
     */
    public function postUpdate($object)
    {
        $this->clearCache($object);
    }

    /**
     * @param Settings $object
     */
    public function preRemove($object)
    {
        $this->clearCache($object);
    }

    public function getFormTheme()
    {
        return array_merge(
            parent::getFormTheme(),
            array('VsavritskySettingsBundle:Form:setting_value_edit.html.twig')
        );
    }

    /**
     * @param Settings $object
     */
    private function clearCache(Settings $object)
    {
        /** @var \Vsavritsky\SettingsBundle\Service\Settings $settings */
        $settings = $this->getConfigurationPool()->getContainer()->get('vsavritsky_settings.settings');
        $settings->clearCache($object->getName());
        if ($object->getCategory()) {
            $settings->clearGroupCache($object->getCategory()->getName());
        }
    }

    /**
     * @return bool
     */
    protected function isNewForm()
    {
        return method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix');
    }
}
