import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DataTable } from '@/components/ui/data-table';
import { Plus, Edit, Trash2 } from 'lucide-react';
import { toast } from 'sonner';
import axios from 'axios';

interface WhatsAppTemplateProps {
    templates: {
        data: Array<{
            id: number;
            name: string;
            language: string;
            category: string;
            template_id: string;
            content: string;
            status: string;
            variables: string[];
            created_at: string;
        }>;
        current_page: number;
        last_page: number;
        total: number;
    };
}

export default function Templates() {
    const { t } = useTranslation();
    const { templates } = usePage<WhatsAppTemplateProps>().props;
    const [showForm, setShowForm] = useState(false);
    const [editData, setEditData] = useState<any>(null);
    const [form, setForm] = useState({
        name: '',
        language: 'id',
        category: '',
        template_id: '',
        content: '',
        variables: [] as string[],
    });
    const [newVariable, setNewVariable] = useState('');

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            if (editData) {
                await axios.put(route('whatsapp.templates.update', editData.id), form);
                toast.success(t('Template updated successfully.'));
            } else {
                await axios.post(route('whatsapp.templates.store'), form);
                toast.success(t('Template created successfully.'));
            }
            setShowForm(false);
            setEditData(null);
            setForm({ name: '', language: 'id', category: '', template_id: '', content: '', variables: [] });
            router.reload();
        } catch (error) {
            toast.error(t('Failed to save template.'));
        }
    };

    const handleEdit = (tpl: any) => {
        setEditData(tpl);
        setForm({
            name: tpl.name,
            language: tpl.language,
            category: tpl.category || '',
            template_id: tpl.template_id || '',
            content: tpl.content,
            variables: tpl.variables || [],
        });
        setShowForm(true);
    };

    const handleDelete = async (id: number) => {
        if (confirm(t('Are you sure?'))) {
            try {
                await axios.delete(route('whatsapp.templates.destroy', id));
                toast.success(t('Template deleted.'));
                router.reload();
            } catch (error) {
                toast.error(t('Failed to delete template.'));
            }
        }
    };

    const addVariable = () => {
        if (newVariable.trim()) {
            setForm({ ...form, variables: [...form.variables, newVariable.trim()] });
            setNewVariable('');
        }
    };

    const removeVariable = (index: number) => {
        setForm({ ...form, variables: form.variables.filter((_, i) => i !== index) });
    };

    const columns = [
        { key: 'name', label: t('Name') },
        { key: 'language', label: t('Language') },
        { key: 'category', label: t('Category') },
        { key: 'status', label: t('Status') },
        { key: 'created_at', label: t('Created') },
        {
            key: 'actions',
            label: t('Actions'),
            render: (row: any) => (
                <div className="flex gap-2">
                    <Button size="sm" variant="ghost" onClick={() => handleEdit(row)}>
                        <Edit className="w-4 h-4" />
                    </Button>
                    <Button size="sm" variant="ghost" onClick={() => handleDelete(row.id)}>
                        <Trash2 className="w-4 h-4" />
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title={t('WhatsApp Templates')} />
            <div className="p-6 space-y-6">
                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-bold text-stone-900">{t('WhatsApp Templates')}</h1>
                    <Button onClick={() => { setEditData(null); setForm({ name: '', language: 'id', category: '', template_id: '', content: '', variables: [] }); setShowForm(true); }}>
                        <Plus className="w-4 h-4 mr-2" />
                        {t('Create Template')}
                    </Button>
                </div>

                {showForm && (
                    <Card>
                        <CardContent className="p-6">
                            <h2 className="text-lg font-semibold mb-4">{editData ? t('Edit Template') : t('Create Template')}</h2>
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-stone-700 mb-1">{t('Name')}</label>
                                        <input type="text" className="w-full border rounded-lg px-3 py-2" value={form.name}
                                            onChange={(e) => setForm({ ...form, name: e.target.value })} required />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-stone-700 mb-1">{t('Language')}</label>
                                        <select className="w-full border rounded-lg px-3 py-2" value={form.language}
                                            onChange={(e) => setForm({ ...form, language: e.target.value })}>
                                            <option value="id">Indonesia</option>
                                            <option value="en">English</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-stone-700 mb-1">{t('Category')}</label>
                                        <input type="text" className="w-full border rounded-lg px-3 py-2" value={form.category}
                                            onChange={(e) => setForm({ ...form, category: e.target.value })} />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-stone-700 mb-1">{t('Template ID')}</label>
                                        <input type="text" className="w-full border rounded-lg px-3 py-2" value={form.template_id}
                                            onChange={(e) => setForm({ ...form, template_id: e.target.value })} />
                                    </div>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-stone-700 mb-1">{t('Content')}</label>
                                    <textarea className="w-full border rounded-lg px-3 py-2 min-h-[120px]" value={form.content}
                                        onChange={(e) => setForm({ ...form, content: e.target.value })} required />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-stone-700 mb-1">{t('Variables')}</label>
                                    <div className="flex gap-2 mb-2">
                                        <input type="text" className="flex-1 border rounded-lg px-3 py-2" placeholder="e.g. {{1}}" value={newVariable}
                                            onChange={(e) => setNewVariable(e.target.value)} />
                                        <Button type="button" variant="outline" onClick={addVariable}>{t('Add')}</Button>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        {form.variables.map((v, i) => (
                                            <span key={i} className="inline-flex items-center gap-1 bg-stone-100 px-2 py-1 rounded text-sm">
                                                {v}
                                                <button type="button" onClick={() => removeVariable(i)} className="text-red-500">&times;</button>
                                            </span>
                                        ))}
                                    </div>
                                </div>
                                <div className="flex gap-2">
                                    <Button type="submit">{editData ? t('Update') : t('Save')}</Button>
                                    <Button type="button" variant="outline" onClick={() => setShowForm(false)}>{t('Cancel')}</Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardContent className="p-6">
                        <DataTable
                            columns={columns}
                            data={templates?.data ?? []}
                            pagination={{
                                currentPage: templates?.current_page ?? 1,
                                lastPage: templates?.last_page ?? 1,
                                total: templates?.total ?? 0,
                            }}
                        />
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
