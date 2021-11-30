<?php
interface IApiUsable
{
	public function TraerUno($request, $handler, $args);
	public function TraerTodos($request, $handler);
	public function CargarUno($request, $handler);
	public function BorrarUno($request, $handler);
	public function ModificarUno($request, $handler);
}
