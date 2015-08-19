<?php

namespace Flysap\Scaffold\Traits;

use Flysap\FormBuilder;
#@todo move it to translatable component .. it requires form builder to build translatable elements from columns .

/**
 *
 *
 */

trait Translatable {

    /**
     * Is possible translatable for that field ?
     *
     * @param $element
     * @param $source
     * @return bool
     */
    public function canBeTranslated($element, $source) {
        if(! $this->hasTranslations($source))
            return false;

        return isset($element['translatable'])  || isset($source->translatedAttributes[$element['name']]);
    }

    /**
     * Has translations that entity .
     *
     * @param $source
     * @return bool
     */
    public function hasTranslations($source) {
        return (is_object($source) && $source instanceof \App\Translatable);
    }

    /**
     * Build translated fields .
     *
     * @param $element
     * @param $source
     * @param array $translations
     * @return array|bool
     */
    public function buildTranslated($element, $source, $translations = array()) {
        if(! $this->canBeTranslated($element, $source))
            return false;

        $elements        = [];
        $translations   = $this->getTranslations($element, $source, $translations);

        foreach ($translations as $language => $translation)
            $elements[] = FormBuilder\get_element($element['label'], $element + ['value' => $translation]);

        return $elements;
    }

    /**
     * Get translations for specific column .
     *
     * @param $element
     * @param $source
     * @param array $translations
     * @return array
     */
    public function getTranslations($element, $source, $translations = array()) {
        if(! $translations)
            $translations = $source->translations();

        return array_map(function($translation) use($source, $element) {
            return [$translation => $source->translate($translation)->$element['name']];
        }, $translations);
    }

}