<?php

class ControllerSchemaBlog extends Controller
{
  public function index($data)
  {
    return $this->load->view('schema/blog', $data);
  }
}
