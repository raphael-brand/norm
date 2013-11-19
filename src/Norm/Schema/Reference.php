<?php

namespace Norm\Schema;

class Reference extends Field {

    protected $foreign;
    protected $foreignLabel;

    public function to($foreign, $foreignLabel) {
        $this->foreign = $foreign;
        $this->foreignLabel = $foreignLabel;
        return $this;
    }

    public function input($value, $entry = NULL) {
        if ($this['readonly']) {
            return parent::input($value, $entry);
        }

        $options = array();
        $foreign = \Norm\Norm::factory($this->foreign);
        $entries = $foreign->find();
        foreach ($entries as $entry) {
            $options[] = '<option value="'.$entry->getId().'" '.($entry->getId() === $value ? 'selected' : '').'>'.$entry->get($this->foreignLabel).'</option>';
        }
        return '
            <select name="'.$this['name'].'"><option value="">---</option>'.implode('', $options).'</select>
        ';
    }
}