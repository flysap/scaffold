<?php

namespace Flysap\Scaffold;

use Cartalyst\Tags\TaggableInterface;
use DataExporter\DriverAssets\Eloquent\Exportable;
use Eloquent\ImageAble\ImageAble;
use Eloquent\Meta\MetaAble;
use Eloquent\Translatable\Translatable;
use Laravel\Meta\Eloquent\MetaSeoable;
use Localization as Locale;
use Laravel\Meta;
use Flysap\FormBuilder;
use DataExporter;

abstract class Builder {

    const DEFAULT_TYPE_ELEMENT = 'text';

    /**
     * @var
     */
    protected $source;

    public function __construct($source) {
        $this->setSource($source);
    }

    /**
     * Set source .
     *
     * @param $source
     * @return $this
     */
    public function setSource($source) {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source .
     *
     * @return mixed
     */
    public function getSource() {
        return $this->source;
    }

    /**
     * Apply other packages ..
     *
     * @param array $elements
     * @return array
     * @throws FormBuilder\ElementException
     */
    public function getAppliedPackages(array $elements = array()) {
        $source   = $this->getSource();

        /** Inject additional tabs if there is .. */
        if( method_exists($source, 'inject') ) {
            $injecting = $source->{'inject'}();

            $injecting = !is_array($injecting) ? (array)$injecting : $injecting;

            array_walk($injecting, function($content, $group) use(& $elements, $source) {
                $elements[] = FormBuilder\element_custom( $content instanceof \Closure ? $content($source) : $content, ['group' => $group]);
            });
        }

        /**
         * If Metaable than have meta
         *
         */
        if( $source instanceof MetaSeoable ) {
            $locales = Locale\get_locales();

            foreach($locales as $locale => $options) {
                $meta = Meta\meta_eloquent($source, $locale);

                foreach ($meta->toArray(true) as $key => $value) {
                    $elements[]  = FormBuilder\get_element('text', [
                        'name'  => 'seo['.$locale.']['.$key.']',
                        'value' => $value,
                        'group' => 'Seo',
                        'label' => $locale .' ' . ucfirst($key)
                    ]);
                }
            }
        }

        /**
         * If exportable than can download .
         */
        if( $source instanceof Exportable ) {
            $exporters = DataExporter\get_exporters();

            foreach($exporters as $exporter => $options) {
                $elements[]  = FormBuilder\get_element('link', [
                    'name'  => $exporter,
                    'group' => 'export',
                    'title' => 'Download in ' .ucfirst($exporter),
                    'href'  => '?export='. strtolower($exporter)
                ]);
            }
        }

        /**
         * If Imageable than can have images .
         *
         */
        if( $source instanceof ImageAble ) {
            $images = $source->images;

            foreach ($images as $image)
                $elements[]  = FormBuilder\get_element('image', [
                    'src'  => $image->path,
                    'title'  => $image->title,
                    'group' => 'images',
                ]);

            $elements[]  = FormBuilder\get_element('file', [
                'group' => 'images',
                'label' => 'Upload images',
                'name'  => 'images[]',
            ]);
        }

        /**
         * If source can be translated
         */
        if( $source instanceof Translatable ) {
            $locales = Locale\get_locales();

            foreach($locales as $locale => $attributes) {
                $translation = $source->translate($locale);

                foreach($source->translatedAttributes() as $attribute) {
                    $elements[]  = FormBuilder\get_element('text', [
                        'group' => 'translations',
                        'label' => ucfirst($attribute) . ' ' . $locale,
                        'value' => $translation[$attribute],
                        'name'  => $locale . '['.$attribute.']',
                    ]);
                }
            }
        }

        /** if Metaable than can have meta attributes */
        if( $source instanceof MetaAble ) {
            $meta = $source->meta;

            foreach ($meta as $value)
                $elements[]  = FormBuilder\get_element('text', [
                    'before' => '<a href="#" onclick="$(this).closest(\'div\').remove(); return false;">'._('Remove').'</a>',
                    'name'   => 'meta['.$value->key.']',
                    'group'  => 'meta',
                    'value'  => $value->value,
                    'label'  => ucfirst($value->key)
                ]);


            $addMeta = FormBuilder\element_text(_('New meta'), [
                'onChange' => "$(this).attr('name', 'meta['+$(this).val()+']')"
            ]);

            $elements[] = FormBuilder\element_custom($addMeta->render(), [
                'group' => 'meta'
            ]);
        }

        /**
         * If source can be tagged
         */
        if( $source instanceof TaggableInterface ) {
            $tags = $source->tags;

            foreach($tags as $tag)
                $elements[]  = FormBuilder\get_element('text', [
                    'before' => '<a href="#" onclick="$(this).closest(\'div\').remove(); return false;">'._('Remove').'</a>',
                    'name'   => 'tags[]',
                    'group'  => 'tags',
                    'value'  => $tag->getAttribute('name'),
                ]);

            $addTag = FormBuilder\element_text(_('New tags'), [
                'name'  => 'tags[]',
                'group' => 'tags'
            ]);

            $elements[] = $addTag;
        }

        return $elements;
    }

    /**
     * Render form .
     *
     * @param null $group
     * @return string
     */
    public function render($group = null) {
        $form = $this->build();

        return $form->render($group);
    }

    public function __toString() {
        return $this->render();
    }

    /**
     * Building ..
     *
     * @param array $params
     * @return mixed
     */
    abstract function build($params = array());
}