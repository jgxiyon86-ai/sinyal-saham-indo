<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MessageTemplatePageController extends Controller
{
    private array $religions;

    public function __construct()
    {
        $this->religions = array_keys(config('religions.options', []));
    }

    public function index(Request $request): View
    {
        $query = MessageTemplate::query()->latest();

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->string('event_type'));
        }

        return view('admin.templates', [
            'templates' => $query->paginate(25)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.templates-form', [
            'template' => null,
            'religions' => config('religions.options', []),
        ]);
    }

    public function edit(MessageTemplate $messageTemplate): View
    {
        return view('admin.templates-form', [
            'template' => $messageTemplate,
            'religions' => config('religions.options', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        MessageTemplate::create($this->validatedData($request));

        return redirect()->route('templates.page')->with('status', 'Template berhasil ditambahkan.');
    }

    public function update(Request $request, MessageTemplate $messageTemplate): RedirectResponse
    {
        $messageTemplate->update($this->validatedData($request));

        return redirect()->route('templates.page')->with('status', 'Template berhasil diupdate.');
    }

    public function destroy(MessageTemplate $messageTemplate): RedirectResponse
    {
        $messageTemplate->delete();
        return redirect()->route('templates.page')->with('status', 'Template berhasil dihapus.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'event_type' => ['required', Rule::in(['birthday', 'holiday', 'general'])],
            'religion' => [
                'nullable',
                Rule::in($this->religions),
                Rule::requiredIf($request->input('event_type') === 'holiday'),
            ],
            'content' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
