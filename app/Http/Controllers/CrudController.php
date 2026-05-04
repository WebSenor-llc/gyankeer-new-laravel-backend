<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Generic CRUD controller for master-data modules.
 *
 * Subclasses configure the model class, title, route prefix, list columns,
 * and form fields. This class then handles index/create/store/edit/update
 * /destroy uniformly using shared `crud/index.blade.php` + `crud/form.blade.php`
 * views.
 */
abstract class CrudController extends Controller
{
    /** @var class-string<Model> Eloquent model class name. */
    protected string $modelClass;

    /** Display title (e.g. "Companies", "Departments"). */
    protected string $title = 'Records';

    /** Singular noun (e.g. "Company") used in messages. */
    protected string $singular = 'Record';

    /** URL prefix (e.g. "companies", "departments") — used for route generation. */
    protected string $routeBase;

    /** Columns to show on the index table. Format: ['column' => 'Header']. */
    protected array $listColumns = [];

    /** Searchable columns (used by ?q= query string). */
    protected array $searchable = [];

    /**
     * If non-empty, filters the index/store/update by the active company
     * stored in session. Set to the FK column name on the model
     * (e.g. 'company_id'). Leave null for global master data (banks,
     * salary_components, holidays).
     */
    protected ?string $companyScope = null;

    /**
     * Field definitions for the create/edit form.
     *
     * Each entry: [
     *   'name'    => column name,
     *   'label'   => human label,
     *   'type'    => text|textarea|number|date|time|boolean|select,
     *   'options' => [value => label]   (for select),
     *   'required'=> bool,
     *   'col'     => 1|2|3            (column span on responsive grid; default 1),
     * ]
     */
    abstract protected function fields(): array;

    public function index(Request $req)
    {
        $query = $this->modelClass::query();

        // Apply active-company scope when configured
        if ($this->companyScope) {
            $cid = (int) session('active_company_id', 0);
            if ($cid) {
                $query->where($this->companyScope, $cid);
            }
        }

        if ($req->filled('q') && !empty($this->searchable)) {
            $q = $req->q;
            $query->where(function ($w) use ($q) {
                foreach ($this->searchable as $col) {
                    $w->orWhere($col, 'like', "%$q%");
                }
            });
        }

        $records = $query->orderByDesc((new $this->modelClass)->getKeyName())->paginate(50)->appends($req->query());

        return view('crud.index', [
            'records'      => $records,
            'title'        => $this->title,
            'singular'     => $this->singular,
            'routeBase'    => $this->routeBase,
            'listColumns'  => $this->listColumns,
            'searchable'   => $this->searchable,
            'searchQuery'  => $req->q,
            'pkName'       => (new $this->modelClass)->getKeyName(),
        ]);
    }

    public function create()
    {
        return view('crud.form', [
            'record'    => new $this->modelClass,
            'title'     => $this->title,
            'singular'  => $this->singular,
            'routeBase' => $this->routeBase,
            'fields'    => $this->fields(),
            'isEdit'    => false,
            'pkName'    => (new $this->modelClass)->getKeyName(),
        ]);
    }

    public function store(Request $req)
    {
        $data = $this->normalizeFormData($req);

        // If this controller is company-scoped and the form didn't set the
        // FK explicitly, default it to the active company.
        if ($this->companyScope && empty($data[$this->companyScope])) {
            $data[$this->companyScope] = (int) session('active_company_id', 0) ?: null;
        }

        $rec  = $this->modelClass::create($data);

        return redirect()
            ->route($this->routeBase . '.index')
            ->with('status', $this->singular . ' created successfully.');
    }

    public function edit($id)
    {
        $rec = $this->modelClass::findOrFail($id);

        return view('crud.form', [
            'record'    => $rec,
            'title'     => $this->title,
            'singular'  => $this->singular,
            'routeBase' => $this->routeBase,
            'fields'    => $this->fields(),
            'isEdit'    => true,
            'pkName'    => $rec->getKeyName(),
        ]);
    }

    public function update(Request $req, $id)
    {
        $rec  = $this->modelClass::findOrFail($id);
        $data = $this->normalizeFormData($req);
        $rec->update($data);

        return redirect()
            ->route($this->routeBase . '.index')
            ->with('status', $this->singular . ' updated successfully.');
    }

    public function destroy($id)
    {
        $rec = $this->modelClass::findOrFail($id);
        $rec->delete();

        return redirect()
            ->route($this->routeBase . '.index')
            ->with('status', $this->singular . ' deleted.');
    }

    public function show($id)
    {
        // Master-data views just go to edit; standalone "show" page is rarely needed.
        return redirect()->route($this->routeBase . '.edit', $id);
    }

    /**
     * Normalize form data from request:
     * - missing checkboxes → false
     * - empty strings on nullable fields → null
     */
    protected function normalizeFormData(Request $req): array
    {
        $data = [];
        foreach ($this->fields() as $f) {
            $name = $f['name'];
            $type = $f['type'] ?? 'text';

            if ($type === 'boolean') {
                $data[$name] = $req->boolean($name);
                continue;
            }

            $val = $req->input($name);
            if ($val === '') $val = null;
            $data[$name] = $val;
        }
        return $data;
    }
}
