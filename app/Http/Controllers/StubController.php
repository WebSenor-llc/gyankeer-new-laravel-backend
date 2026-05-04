<?php

namespace App\Http\Controllers;

/**
 * Generic placeholder base controller.
 *
 * Modules that haven't been implemented yet extend this — any route method
 * call (index, create, show, edit, update, destroy, or any custom action
 * declared in routes/web.php) is caught by `__call` and renders the shared
 * placeholder view instead of throwing a 500. Subclasses that DO implement
 * a real method simply declare it with whatever signature they need; that
 * method overrides nothing and `__call` covers everything else.
 */
abstract class StubController extends Controller
{
    /** @var string Display name shown in the placeholder. */
    protected string $title = 'Module';

    /**
     * Catch any controller method that the subclass hasn't explicitly
     * declared (index, store, show, edit, update, destroy, or custom
     * actions like configForm, sheet, statistical, etc.) and render the
     * placeholder. Route parameters arrive in `$arguments`.
     */
    public function __call($name, $arguments)
    {
        $id = null;
        foreach ($arguments as $a) {
            if (is_scalar($a)) { $id = $a; break; }
        }
        return $this->stub($name, $id);
    }

    protected function stub(string $action, $id = null)
    {
        return view('placeholder', [
            'title'  => $this->title,
            'action' => $action . ($id !== null ? ' #'.$id : ''),
        ]);
    }
}
