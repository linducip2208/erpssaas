import { Head, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Plus, Settings, Trash2, Download } from 'lucide-react';
import { usePage } from '@inertiajs/react';

interface Gateway {
  id: number;
  name: string;
  display_name: string;
  format: string;
  description: string;
  is_enabled: boolean;
  is_test_mode: boolean;
  sort_order: number;
  supported_currencies: string[];
}

interface Format {
  value: string;
  label: string;
  description: string;
}

export default function GatewayIndex({ gateways = [], formats = [], presets = [] }: {
  gateways: Gateway[];
  formats: Format[];
  presets: any[];
}) {
  const { t } = useTranslation();

  const formatLabel = (f: string) => formats.find(x => x.value === f)?.label ?? f;

  const handleToggle = (id: number) => {
    router.post(`/gateways/${id}/toggle`, {}, {
      preserveScroll: true,
      onSuccess: () => {},
    });
  };

  const handleDelete = (id: number, name: string) => {
    if (confirm(`Delete gateway "${name}"?`)) {
      router.delete(`/gateways/${id}`, { preserveScroll: true });
    }
  };

  const handleImportPreset = (presetName: string) => {
    router.get('/gateways/presets/load', { name: presetName }, {
      preserveScroll: true,
      onSuccess: () => {
        router.visit('/gateways/create', { method: 'get', data: { preset: presetName } });
      },
    });
  };

  return (
    <AuthenticatedLayout breadcrumbs={[{ label: t('Payment Gateways') }]} pageTitle={t('Payment Gateways')}>
      <Head title={t('Payment Gateways')} />

      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <h2 className="text-2xl font-bold tracking-tight">{t('Payment Gateways')}</h2>
            <p className="text-muted-foreground mt-1">
              {t('Manage payment gateways. Users can configure their own API keys.')}
            </p>
          </div>
          <a href="/gateways/create">
            <Button>
              <Plus className="h-4 w-4 mr-2" />
              {t('Add Gateway')}
            </Button>
          </a>
        </div>

        {/* Presets Quick-Add */}
        {presets.length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle className="text-lg">{t('Quick Add from Presets')}</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3">
                {presets.map((preset: any) => (
                  <a
                    key={preset.name}
                    href={`/gateways/create?preset=${preset.name}`}
                    className="flex flex-col items-center gap-1 p-3 rounded-lg border border-border hover:bg-muted transition-colors text-center cursor-pointer"
                  >
                    <Download className="h-5 w-5 text-muted-foreground mb-1" />
                    <span className="text-xs font-medium">{preset.display_name}</span>
                    <span className="text-[10px] text-muted-foreground">{preset.supported_currencies?.join(', ')}</span>
                  </a>
                ))}
              </div>
            </CardContent>
          </Card>
        )}

        {/* Gateway List */}
        <Card>
          <CardHeader>
            <CardTitle className="text-lg">
              {t('Configured Gateways')} ({gateways.length})
            </CardTitle>
          </CardHeader>
          <CardContent>
            {gateways.length === 0 ? (
              <div className="text-center py-8 text-muted-foreground">
                <p>{t('No payment gateways configured yet.')}</p>
                <p className="text-sm mt-1">{t('Click "Add Gateway" or use a preset above to get started.')}</p>
              </div>
            ) : (
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>{t('Gateway')}</TableHead>
                    <TableHead>{t('Format')}</TableHead>
                    <TableHead>{t('Currencies')}</TableHead>
                    <TableHead>{t('Mode')}</TableHead>
                    <TableHead>{t('Status')}</TableHead>
                    <TableHead className="w-20">{t('Actions')}</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {gateways.map((gateway: Gateway) => (
                    <TableRow key={gateway.id}>
                      <TableCell>
                        <div className="font-medium">{gateway.display_name || gateway.name}</div>
                        <div className="text-xs text-muted-foreground">{gateway.name}</div>
                      </TableCell>
                      <TableCell>
                        <Badge variant="outline">{formatLabel(gateway.format)}</Badge>
                      </TableCell>
                      <TableCell>
                        <div className="flex flex-wrap gap-1">
                          {(gateway.supported_currencies || []).slice(0, 3).map((c: string) => (
                            <Badge key={c} variant="secondary" className="text-[10px]">{c}</Badge>
                          ))}
                          {(gateway.supported_currencies || []).length > 3 && (
                            <Badge variant="secondary" className="text-[10px]">+{gateway.supported_currencies.length - 3}</Badge>
                          )}
                        </div>
                      </TableCell>
                      <TableCell>
                        {gateway.is_test_mode
                          ? <Badge variant="secondary">{t('Test')}</Badge>
                          : <Badge>{t('Live')}</Badge>
                        }
                      </TableCell>
                      <TableCell>
                        <Switch
                          checked={gateway.is_enabled}
                          onCheckedChange={() => handleToggle(gateway.id)}
                        />
                      </TableCell>
                      <TableCell>
                        <div className="flex gap-1">
                          <a href={`/gateways/${gateway.id}/edit`}>
                            <Button variant="ghost" size="icon" title={t('Edit')}>
                              <Settings className="h-4 w-4" />
                            </Button>
                          </a>
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleDelete(gateway.id, gateway.display_name || gateway.name)}
                            title={t('Delete')}
                          >
                            <Trash2 className="h-4 w-4 text-destructive" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            )}
          </CardContent>
        </Card>
      </div>
    </AuthenticatedLayout>
  );
}
