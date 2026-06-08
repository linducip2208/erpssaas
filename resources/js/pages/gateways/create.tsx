import { useState, useEffect } from 'react';
import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, Info } from 'lucide-react';

interface Format {
  value: string;
  label: string;
  description: string;
}

function getQueryParam(name: string): string | null {
  const params = new URLSearchParams(window.location.search);
  return params.get(name);
}

export default function GatewayForm({ gateway, formats = [], configKeys = null, presets = [] }: any) {
  const { t } = useTranslation();
  const isEdit = !!gateway;
  const presetName = getQueryParam('preset');

  const preset = presetName ? presets.find((p: any) => p.name === presetName) : null;

  const [formData, setFormData] = useState({
    name: gateway?.name ?? preset?.name ?? '',
    format: gateway?.format ?? preset?.format ?? 'stripe',
    display_name: gateway?.display_name ?? preset?.display_name ?? '',
    description: gateway?.description ?? preset?.description ?? '',
    base_url: gateway?.base_url ?? preset?.base_url ?? '',
    api_key: '',
    api_secret: '',
    webhook_secret: '',
    extra_config: gateway?.extra_config ? JSON.stringify(gateway.extra_config, null, 2) : (preset?.extra_config ? JSON.stringify(preset.extra_config, null, 2) : '{}'),
    supported_currencies: gateway?.supported_currencies ?? preset?.supported_currencies ?? [],
    is_enabled: gateway?.is_enabled ?? false,
    is_test_mode: gateway?.is_test_mode ?? true,
    logo: gateway?.logo ?? preset?.logo ?? '',
    sort_order: gateway?.sort_order ?? preset?.sort_order ?? 0,
  });

  const [currencyInput, setCurrencyInput] = useState('');

  const addCurrency = () => {
    const c = currencyInput.trim().toUpperCase();
    if (c && !formData.supported_currencies.includes(c)) {
      setFormData({ ...formData, supported_currencies: [...formData.supported_currencies, c] });
    }
    setCurrencyInput('');
  };

  const removeCurrency = (c: string) => {
    setFormData({ ...formData, supported_currencies: formData.supported_currencies.filter((x: string) => x !== c) });
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const data = {
      ...formData,
      extra_config: formData.extra_config,
    };

    if (isEdit) {
      router.put(`/gateways/${gateway.id}`, data, {
        onSuccess: () => router.visit('/gateways'),
      });
    } else {
      router.post('/gateways', data, {
        onSuccess: () => router.visit('/gateways'),
      });
    }
  };

  return (
    <AuthenticatedLayout
      breadcrumbs={[{ label: t('Payment Gateways'), href: '/gateways' }, { label: isEdit ? t('Edit Gateway') : t('Add Gateway') }]}
      pageTitle={isEdit ? t('Edit Gateway') : t('Add Gateway')}
    >
      <Head title={isEdit ? t('Edit Gateway') : t('Add Gateway')} />

      <form onSubmit={handleSubmit} className="max-w-2xl space-y-6">
        <div className="flex items-center gap-4">
          <a href="/gateways" className="text-muted-foreground hover:text-foreground">
            <ArrowLeft className="h-5 w-5" />
          </a>
          <h2 className="text-2xl font-bold tracking-tight">
            {isEdit ? t('Edit Gateway') : t('Add Gateway')}
          </h2>
        </div>

        {preset && (
          <Card className="border-primary/30 bg-primary/5">
            <CardContent className="p-4 flex items-start gap-3">
              <Info className="h-5 w-5 text-primary mt-0.5" />
              <div>
                <p className="font-medium text-sm">{t('Preset loaded:')} {preset.display_name}</p>
                <p className="text-xs text-muted-foreground">{preset.description}</p>
                <p className="text-xs text-muted-foreground mt-1">
                  {t('Supported currencies:')} {preset.supported_currencies?.join(', ')}
                </p>
              </div>
            </CardContent>
          </Card>
        )}

        {/* Basic Info */}
        <Card>
          <CardHeader><CardTitle>{t('Basic Information')}</CardTitle></CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <Label htmlFor="name">{t('System Name')} *</Label>
                <Input id="name" value={formData.name} onChange={e => setFormData({ ...formData, name: e.target.value })} placeholder="e.g. stripe, midtrans, xendit" required />
                <p className="text-xs text-muted-foreground mt-1">{t('Unique identifier. Use lowercase, no spaces.')}</p>
              </div>
              <div>
                <Label htmlFor="display_name">{t('Display Name')}</Label>
                <Input id="display_name" value={formData.display_name} onChange={e => setFormData({ ...formData, display_name: e.target.value })} placeholder="e.g. Stripe, Midtrans" />
              </div>
            </div>
            <div>
              <Label htmlFor="description">{t('Description')}</Label>
              <Textarea id="description" value={formData.description} onChange={e => setFormData({ ...formData, description: e.target.value })} rows={2} />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <Label htmlFor="format">{t('Format')} *</Label>
                <Select value={formData.format} onValueChange={v => setFormData({ ...formData, format: v })}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    {formats.map((f: Format) => (
                      <SelectItem key={f.value} value={f.value}>
                        <span className="font-medium">{f.label}</span>
                        <span className="block text-xs text-muted-foreground">{f.description}</span>
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label htmlFor="sort_order">{t('Sort Order')}</Label>
                <Input id="sort_order" type="number" value={formData.sort_order} onChange={e => setFormData({ ...formData, sort_order: parseInt(e.target.value) || 0 })} />
              </div>
            </div>
          </CardContent>
        </Card>

        {/* API Config */}
        <Card>
          <CardHeader><CardTitle>{t('API Configuration')}</CardTitle></CardHeader>
          <CardContent className="space-y-4">
            <div>
              <Label htmlFor="base_url">{t('Base URL')}</Label>
              <Input id="base_url" value={formData.base_url} onChange={e => setFormData({ ...formData, base_url: e.target.value })} placeholder="https://api.example.com" />
            </div>
            <div>
              <Label htmlFor="api_key">{t('API Key / Publishable Key')}</Label>
              <Input id="api_key" type="password" value={formData.api_key} onChange={e => setFormData({ ...formData, api_key: e.target.value })} placeholder={isEdit ? '(leave empty to keep current)' : 'pk_xxx'} />
              {isEdit && <p className="text-xs text-muted-foreground mt-1">{t('Leave empty to keep the current key.')}</p>}
            </div>
            <div>
              <Label htmlFor="api_secret">{t('Secret Key / Private Key')}</Label>
              <Input id="api_secret" type="password" value={formData.api_secret} onChange={e => setFormData({ ...formData, api_secret: e.target.value })} placeholder={isEdit ? '(leave empty to keep current)' : 'sk_xxx'} />
              {isEdit && <p className="text-xs text-muted-foreground mt-1">{t('Leave empty to keep the current key.')}</p>}
            </div>
            <div>
              <Label htmlFor="webhook_secret">{t('Webhook Secret (optional)')}</Label>
              <Input id="webhook_secret" type="password" value={formData.webhook_secret} onChange={e => setFormData({ ...formData, webhook_secret: e.target.value })} placeholder="whsec_xxx" />
            </div>
            <div>
              <Label htmlFor="extra_config">{t('Extra Config (JSON)')}</Label>
              <Textarea id="extra_config" value={formData.extra_config} onChange={e => setFormData({ ...formData, extra_config: e.target.value })} rows={6} className="font-mono text-sm" placeholder='{"create_endpoint": "/v1/payments", "success_statuses": "paid,completed"}' />
              <p className="text-xs text-muted-foreground mt-1">{t('Gateway-specific configuration in JSON format.')}</p>
            </div>
          </CardContent>
        </Card>

        {/* Currencies */}
        <Card>
          <CardHeader><CardTitle>{t('Supported Currencies')}</CardTitle></CardHeader>
          <CardContent className="space-y-3">
            <div className="flex gap-2">
              <Input value={currencyInput} onChange={e => setCurrencyInput(e.target.value)} placeholder="e.g. IDR, USD, MYR" onKeyDown={e => e.key === 'Enter' && (e.preventDefault(), addCurrency())} />
              <Button type="button" variant="outline" onClick={addCurrency}>{t('Add')}</Button>
            </div>
            <div className="flex flex-wrap gap-2">
              {formData.supported_currencies.map((c: string) => (
                <Badge key={c} variant="secondary" className="cursor-pointer" onClick={() => removeCurrency(c)}>
                  {c} ✕
                </Badge>
              ))}
            </div>
          </CardContent>
        </Card>

        {/* Settings */}
        <Card>
          <CardHeader><CardTitle>{t('Settings')}</CardTitle></CardHeader>
          <CardContent className="space-y-4">
            <div className="flex items-center justify-between">
              <div>
                <Label>{t('Enable Gateway')}</Label>
                <p className="text-xs text-muted-foreground">{t('When enabled, this gateway will appear as a payment option.')}</p>
              </div>
              <Switch checked={formData.is_enabled} onCheckedChange={v => setFormData({ ...formData, is_enabled: v })} />
            </div>
            <div className="flex items-center justify-between">
              <div>
                <Label>{t('Test Mode')}</Label>
                <p className="text-xs text-muted-foreground">{t('Use sandbox/test environment instead of production.')}</p>
              </div>
              <Switch checked={formData.is_test_mode} onCheckedChange={v => setFormData({ ...formData, is_test_mode: v })} />
            </div>
            <div>
              <Label htmlFor="logo">{t('Logo URL (optional)')}</Label>
              <Input id="logo" value={formData.logo} onChange={e => setFormData({ ...formData, logo: e.target.value })} placeholder="/marketing/gateways/stripe.svg" />
            </div>
          </CardContent>
        </Card>

        <div className="flex gap-3">
          <Button type="submit" size="lg">
            {isEdit ? t('Update Gateway') : t('Create Gateway')}
          </Button>
          <a href="/gateways">
            <Button type="button" variant="outline" size="lg">{t('Cancel')}</Button>
          </a>
        </div>
      </form>
    </AuthenticatedLayout>
  );
}
