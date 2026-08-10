<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Product;
use App\Models\Quote;
use App\Services\Tools\ToolManager;
use Illuminate\Http\Request;

class ToolsController extends Controller
{
    public function __construct(private ToolManager $tools) {}

    public function index()
    {
        $tenant = tenant();

        $agent = $this->agent();

        return inertia('Tools', [
            'tools' => $this->tools->catalog(),
            'enabled' => $agent->tools ?? [],
            'products' => Product::query()->orderBy('created_at')->get(),
            'quotes' => Quote::query()
                ->with('contact:id,name,email,phone')
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(),
        ]);
    }

    public function updateTools(Request $request)
    {
        $available = $this->tools->available();

        $data = $request->validate([
            'tools' => ['nullable', 'array'],
            'tools.*' => ['required', 'string', 'in:'.implode(',', $available)],
        ]);

        $agent = $this->agent();
        $agent->update(['tools' => array_values(array_unique($data['tools'] ?? []))]);

        return back()->with('success', 'Herramientas del asistente actualizadas.');
    }

    public function storeProduct(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'unit' => ['nullable', 'string', 'max:50'],
            'category' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Product::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'currency' => $data['currency'],
            'unit' => $data['unit'] ?? null,
            'category' => $data['category'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return back()->with('success', 'Producto añadido al catálogo.');
    }

    public function updateProduct(Request $request, Product $product)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'unit' => ['nullable', 'string', 'max:50'],
            'category' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $product->update($data);

        return back()->with('success', 'Producto actualizado.');
    }

    public function destroyProduct(Product $product)
    {
        $product->delete();

        return back()->with('success', 'Producto eliminado.');
    }

    public function setQuoteStatus(Request $request, Quote $quote)
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:draft,sent,accepted,rejected'],
        ]);

        $quote->update(['status' => $data['status']]);

        return back()->with('success', 'Estado de la cotización actualizado.');
    }

    private function agent(): Agent
    {
        return Agent::query()->firstOrCreate(
            ['tenant_id' => tenant()->id, 'slug' => 'assistant'],
            [
                'name' => 'Asistente de '.tenant()->name,
                'instructions' => 'Responde con la información autorizada del negocio.',
                'tools' => [],
                'guardrails' => [
                    'tone' => 'profesional y cercano',
                    'language' => 'español',
                    'welcome' => 'Hola, ¿en qué puedo ayudarte?',
                    'escalation' => 'Cuando no tengas información suficiente, deriva a un asesor humano.',
                ],
                'is_active' => true,
            ]
        );
    }
}
