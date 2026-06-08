import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';

interface CreateProps {
    group?: any;
    onSuccess: () => void;
}

export default function Create({ group, onSuccess }: CreateProps) {
    const { t } = useTranslation();
    const isEdit = !!group;

    const { data, setData, post, put, processing, errors } = useForm({
        name: group?.name ?? '',
        target_module: group?.target_module ?? '',
        target_type: group?.target_type ?? '',
        is_active: group?.is_active ?? true,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            put(route('custom-field.groups.update', group.id), {
                onSuccess: () => onSuccess()
            });
        } else {
            post(route('custom-field.groups.store'), {
                onSuccess: () => onSuccess()
            });
        }
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{isEdit ? t('Edit Custom Field Group') : t('Create Custom Field Group')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="name">{t('Name')}</Label>
                    <Input
                        id="name"
                        type="text"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder={t('Enter Group Name')}
                        required
                    />
                    <InputError message={errors.name} />
                </div>

                <div>
                    <Label required>{t('Target Module')}</Label>
                    <Select value={data.target_module} onValueChange={(value) => setData('target_module', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Module')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="sales">{t('Sales')}</SelectItem>
                            <SelectItem value="inventory">{t('Inventory')}</SelectItem>
                            <SelectItem value="hr">{t('HR')}</SelectItem>
                            <SelectItem value="accounting">{t('Accounting')}</SelectItem>
                            <SelectItem value="crm">{t('CRM')}</SelectItem>
                            <SelectItem value="products">{t('Products')}</SelectItem>
                            <SelectItem value="purchases">{t('Purchases')}</SelectItem>
                            <SelectItem value="contracts">{t('Contracts')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.target_module} />
                </div>

                <div>
                    <Label>{t('Target Type')}</Label>
                    <Input
                        type="text"
                        value={data.target_type}
                        onChange={(e) => setData('target_type', e.target.value)}
                        placeholder={t('e.g. lead, contact, account')}
                    />
                    <InputError message={errors.target_type} />
                </div>

                <div className="flex items-center gap-3">
                    <Label>{t('Active')}</Label>
                    <Switch
                        checked={data.is_active}
                        onCheckedChange={(checked) => setData('is_active', checked)}
                    />
                </div>

                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" onClick={onSuccess}>
                        {t('Cancel')}
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing ? (isEdit ? t('Updating...') : t('Creating...')) : (isEdit ? t('Update') : t('Create'))}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}
