<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Resources;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** CRUD generik untuk resource di App\Admin\Resources. */
class CrudController extends Controller
{
    protected function res(string $resource): array
    {
        $res = Resources::get($resource);
        abort_unless($res, 404);

        return $res;
    }

    protected function find(array $res, $id): Model
    {
        $q = $res['model']::query()->with($res['with'] ?? []);
        if (isset($res['scope'])) {
            ($res['scope'])($q);
        }

        return $q->findOrFail($id);
    }

    public function index(Request $request, string $resource)
    {
        $res = $this->res($resource);
        $q = $res['model']::query()->with($res['with'] ?? []);
        if (isset($res['scope'])) {
            ($res['scope'])($q);
        }
        if (! empty($res['withCount'])) {
            $q->withCount($res['withCount']);
        }
        if ($term = trim((string) $request->query('q'))) {
            $q->where(function ($w) use ($res, $term) {
                foreach ($res['search'] ?? [] as $col) {
                    $w->orWhere($col, 'like', '%'.$term.'%');
                }
            });
        }
        foreach ($res['filters'] ?? [] as $field => $f) {
            if (($v = $request->query($field)) !== null && $v !== '') {
                ($f['type'] ?? null) === 'date' ? $q->whereDate($field, $v) : $q->where($field, $v);
            }
        }
        [$col, $dir] = $res['order'] ?? ['id', 'desc'];
        $rows = $q->orderBy($col, $dir)->paginate(25)->withQueryString();
        $total = $res['model']::count();

        return view('admin.crud.index', compact('res', 'resource', 'rows', 'total'));
    }

    public function create(string $resource)
    {
        $res = $this->res($resource);
        abort_if($res['noCreate'] ?? false, 404);
        $model = new $res['model'];
        foreach ($res['fields'] as $f) {
            if (array_key_exists('default', $f)) {
                $model->{$f['name']} = is_callable($f['default']) ? $f['default']() : $f['default'];
            }
        }

        return view('admin.crud.form', compact('res', 'resource', 'model'));
    }

    public function store(Request $request, string $resource)
    {
        $res = $this->res($resource);
        abort_if($res['noCreate'] ?? false, 404);

        return $this->save($request, $res, $resource, new $res['model']);
    }

    public function edit(string $resource, $id)
    {
        $res = $this->res($resource);
        abort_if($res['noEdit'] ?? false, 404);
        $model = $this->find($res, $id);

        return view('admin.crud.form', compact('res', 'resource', 'model'));
    }

    public function update(Request $request, string $resource, $id)
    {
        $res = $this->res($resource);
        abort_if($res['noEdit'] ?? false, 404);

        return $this->save($request, $res, $resource, $this->find($res, $id));
    }

    protected function save(Request $request, array $res, string $resource, Model $model)
    {
        $isNew = ! $model->exists;
        $rules = [];
        foreach ($res['fields'] as $f) {
            if (in_array($f['type'], ['readonly', 'toggle'], true)) {
                continue;
            }
            $rules[$f['name']] = is_callable($f['rules'] ?? null) ? $f['rules']($model) : ($f['rules'] ?? 'nullable');
        }
        $data = $request->validate($rules, [], collect($res['fields'])->pluck('label', 'name')->all());
        // Isi editor teks disaring: hanya format yang aman yang disimpan
        foreach ($res['fields'] as $f) {
            if (($f['type'] ?? '') === 'rich' && array_key_exists($f['name'], $data)) {
                $data[$f['name']] = \App\Support\Rich::sanitize($data[$f['name']]) ?: null;
            }
        }

        foreach ($res['fields'] as $f) {
            if ($f['type'] === 'toggle') {
                $data[$f['name']] = $request->boolean($f['name']);
            }
            if ($f['type'] === 'image') {
                unset($data[$f['name']]);
                if ($request->boolean($f['name'].'_remove') && $model->{$f['name']}) {
                    Storage::disk('public')->delete($model->{$f['name']});
                    $data[$f['name']] = null;
                }
                if ($request->hasFile($f['name'])) {
                    if ($model->{$f['name']}) {
                        Storage::disk('public')->delete($model->{$f['name']});
                    }
                    $data[$f['name']] = $request->file($f['name'])->store($f['dir'] ?? 'uploads', 'public');
                }
            }
        }

        if (isset($res['before'])) {
            $res['before']($model, $data, $isNew);
        }
        if ($isNew && ($res['sortable'] ?? false)) {
            $data['sort'] = ($res['model']::max('sort') ?? 0) + 1;
        }
        $model->fill($data)->save();
        if (isset($res['after'])) {
            $res['after']($model);
        }

        $label = isset($res['label']) ? $res['label']($model) : $model->getKey();
        ActivityLog::record(($isNew ? 'Menambahkan ' : 'Mengubah ').$res['one'].' "'.$label.'"');

        return redirect()->route('admin.crud.index', $resource)->with('ok', $isNew ? 'Data ditambahkan.' : 'Perubahan disimpan.');
    }

    public function destroy(string $resource, $id)
    {
        $res = $this->res($resource);
        abort_if($res['noDelete'] ?? false, 404);
        $model = $this->find($res, $id);
        if (isset($res['canDelete']) && ($msg = $res['canDelete']($model))) {
            return back()->with('err', $msg);
        }
        foreach ($res['fields'] as $f) {
            if ($f['type'] === 'image' && $model->{$f['name']}) {
                Storage::disk('public')->delete($model->{$f['name']});
            }
        }
        foreach ($res['files'] ?? [] as $f) {
            if ($model->{$f}) {
                Storage::disk('local')->delete($model->{$f});
            }
        }
        $label = isset($res['label']) ? $res['label']($model) : $model->getKey();
        $model->delete();
        ActivityLog::record('Menghapus '.$res['one'].' "'.$label.'"');

        return back()->with('ok', ucfirst($res['one']).' dihapus.');
    }

    public function toggle(string $resource, $id, string $field)
    {
        $res = $this->res($resource);
        $allowed = collect($res['fields'])->where('type', 'toggle')->pluck('name')->all();
        abort_unless(in_array($field, $allowed, true), 404);
        $model = $this->find($res, $id);
        if ($resource === 'users' && $model->id === auth()->id()) {
            return back()->with('err', 'Akun Anda sendiri harus tetap aktif.');
        }
        $model->{$field} = ! $model->{$field};
        $model->save();
        if (isset($res['after'])) {
            $res['after']($model);
        }
        $label = isset($res['label']) ? $res['label']($model) : $model->getKey();
        ActivityLog::record(($model->{$field} ? 'Mengaktifkan ' : 'Menonaktifkan ').$field.' pada '.$res['one'].' "'.$label.'"');

        return back()->with('ok', $model->{$field} ? 'Ditampilkan.' : 'Disembunyikan.');
    }

    public function move(string $resource, $id, string $dir)
    {
        $res = $this->res($resource);
        abort_unless($res['sortable'] ?? false, 404);
        $items = $res['model']::orderBy('sort')->orderBy('id')->get()->values();
        $i = $items->search(fn ($m) => $m->getKey() == $id);
        $j = $dir === 'up' ? $i - 1 : $i + 1;
        if ($i === false || $j < 0 || $j >= $items->count()) {
            return back();
        }
        $list = $items->all();
        [$list[$i], $list[$j]] = [$list[$j], $list[$i]];
        foreach ($list as $pos => $m) {
            $m->update(['sort' => $pos + 1]);
        }
        ActivityLog::record('Mengubah urutan '.$res['one']);

        return back();
    }
}
