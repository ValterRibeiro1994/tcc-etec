<?php
class Data {
    private string $data;
    public function __construct(string $data = null)
    {
        if ($data != null) $this->data;
    }

    public function getData(){
        if ($this->data == null) throw new Exception("Data não enviada");
        return $this->data;
    }

    public function setData(string $data){
        $this->data = $data;
    }
}