import { useState, useEffect } from 'react';
import { cupboardsApi } from '../api';
import Modal from '../components/Modal';
import { Plus, Edit2, Trash2, Archive } from 'lucide-react';
import toast from 'react-hot-toast';

export default function CupboardsPage() {
  const [cupboards, setCupboards] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editItem, setEditItem] = useState(null);
  const [form, setForm] = useState({ name: '', description: '' });

  const fetch = () => {
    cupboardsApi.getAll()
      .then(res => setCupboards(res.data))
      .catch(() => toast.error('Failed to load cupboards'))
      .finally(() => setLoading(false));
  };

  useEffect(() => { fetch(); }, []);

  const openCreate = () => { setEditItem(null); setForm({ name: '', description: '' }); setShowModal(true); };
  const openEdit = (item) => { setEditItem(item); setForm({ name: item.name, description: item.description || '' }); setShowModal(true); };

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      if (editItem) {
        await cupboardsApi.update(editItem.id, form);
        toast.success('Cupboard updated');
      } else {
        await cupboardsApi.create(form);
        toast.success('Cupboard created');
      }
      setShowModal(false);
      fetch();
    } catch (err) {
      toast.error(err.response?.data?.message || 'Error');
    }
  };

  const handleDelete = async (id) => {
    if (!confirm('Delete this cupboard and all its places/items?')) return;
    try {
      await cupboardsApi.delete(id);
      toast.success('Cupboard deleted');
      fetch();
    } catch (err) {
      toast.error(err.response?.data?.message || 'Error');
    }
  };

  if (loading) return <div className="loading-spinner"><div className="spinner"></div></div>;

  return (
    <>
      <div className="page-header">
        <div className="page-header-actions">
          <div><h2>Cupboards</h2><p>Manage storage cupboards</p></div>
          <button className="btn btn-primary" onClick={openCreate}><Plus size={16} /> Add Cupboard</button>
        </div>
      </div>
      <div className="page-body">
        <div className="table-container">
          <table>
            <thead><tr><th>Name</th><th>Description</th><th>Places</th><th>Created By</th><th>Actions</th></tr></thead>
            <tbody>
              {cupboards.map(c => (
                <tr key={c.id}>
                  <td style={{ fontWeight: 600 }}>{c.name}</td>
                  <td className="text-muted">{c.description || '—'}</td>
                  <td><span className="badge badge-active">{c.places_count} places</span></td>
                  <td className="text-muted text-sm">{c.creator?.name}</td>
                  <td>
                    <div className="actions-cell">
                      <button className="btn btn-sm btn-secondary" onClick={() => openEdit(c)}><Edit2 size={14} /></button>
                      <button className="btn btn-sm btn-danger" onClick={() => handleDelete(c.id)}><Trash2 size={14} /></button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          {cupboards.length === 0 && (
            <div className="empty-state"><Archive size={48} /><h3>No cupboards</h3><p>Add your first cupboard</p></div>
          )}
        </div>
      </div>

      <Modal isOpen={showModal} onClose={() => setShowModal(false)} title={editItem ? 'Edit Cupboard' : 'Create Cupboard'}>
        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label className="form-label">Name</label>
            <input className="form-input" value={form.name} onChange={e => setForm({...form, name: e.target.value})} required />
          </div>
          <div className="form-group">
            <label className="form-label">Description</label>
            <textarea className="form-textarea" value={form.description} onChange={e => setForm({...form, description: e.target.value})} />
          </div>
          <div style={{ display: 'flex', gap: '12px', justifyContent: 'flex-end', marginTop: '20px' }}>
            <button type="button" className="btn btn-secondary" onClick={() => setShowModal(false)}>Cancel</button>
            <button type="submit" className="btn btn-primary">{editItem ? 'Update' : 'Create'}</button>
          </div>
        </form>
      </Modal>
    </>
  );
}
