<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiFaqItem;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function index(Request $request): View
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');
        $items = AiFaqItem::query()->where('project_id', $project->id)->latest()->paginate(20);

        return view('admin.faq.index', compact('items'));
    }

    public function create(): View
    {
        return view('admin.faq.form', ['item' => new AiFaqItem(['locale' => 'uk', 'is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');
        $data = $this->validated($request);
        $data['project_id'] = $project->id;
        AiFaqItem::query()->create($data);

        return redirect()->route('admin.faq.index')->with('success', 'FAQ добавлен.');
    }

    public function edit(AiFaqItem $faq): View
    {
        return view('admin.faq.form', ['item' => $faq]);
    }

    public function update(Request $request, AiFaqItem $faq): RedirectResponse
    {
        $faq->update($this->validated($request));

        return redirect()->route('admin.faq.index')->with('success', 'FAQ обновлён.');
    }

    public function destroy(AiFaqItem $faq): RedirectResponse
    {
        $faq->delete();

        return redirect()->route('admin.faq.index')->with('success', 'FAQ удалён.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'question' => ['required', 'string'],
            'answer' => ['required', 'string'],
            'locale' => ['required', 'string', 'max:10'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
