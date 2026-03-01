import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import {
  BarChart3, Users, Send, TrendingUp, LogOut,
  CheckCircle2, AlertCircle,
  Smartphone, Search, Plus, Trash2, History
} from 'lucide-react';

interface Signal {
  id: number;
  stock_code: string;
  signal_type: string;
  entry_price: string;
  take_profit: string;
  stop_loss: string;
  title: string;
  wa_blasted_at: string | null;
}

const App: React.FC = () => {
  const [activeTab, setActiveTab] = useState<'signals' | 'gateway' | 'history'>('signals');
  const [signals, setSignals] = useState<Signal[]>([]);
  const [notif, setNotif] = useState<{ msg: string, type: 'success' | 'error' } | null>(null);

  // Stats Mock
  const stats = [
    { label: 'Active Signals', value: '12', icon: TrendingUp, color: '#10b981' },
    { label: 'Total Clients', value: '1,240', icon: Users, color: '#3b82f6' },
    { label: 'WA Sent Today', value: '850', icon: Send, color: '#1e3a8a' },
  ];

  useEffect(() => {
    setSignals([
      { id: 1, stock_code: 'BBCA', signal_type: 'Buy', entry_price: '10200', take_profit: '10500', stop_loss: '10000', title: 'Swing BBCA', wa_blasted_at: '2026-02-26 12:00' },
      { id: 2, stock_code: 'GOTO', signal_type: 'Sell', entry_price: '50', take_profit: '45', stop_loss: '55', title: 'Day GOTO', wa_blasted_at: null },
      { id: 3, stock_code: 'TLKM', signal_type: 'Buy', entry_price: '3800', take_profit: '4000', stop_loss: '3750', title: 'Value TLKM', wa_blasted_at: null },
    ]);
  }, []);

  const showNotif = (msg: string, type: 'success' | 'error') => {
    setNotif({ msg, type });
    setTimeout(() => setNotif(null), 3000);
  };

  const handleWaBlast = async (signalId: number) => {
    showNotif('Sedang mengirim WA Blast...', 'success');
    setTimeout(() => {
      setSignals(prev => prev.map(s => s.id === signalId ? { ...s, wa_blasted_at: 'Just now' } : s));
      showNotif('Sinyal berhasil di-blast ke semua grup!', 'success');
    }, 1500);
  };

  return (
    <div className="dashboard-container">
      <aside className="sidebar">
        <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '1rem' }}>
          <div style={{ background: 'white', padding: '8px', borderRadius: '12px' }}>
            <TrendingUp color="#1e3a8a" size={24} />
          </div>
          <h2 style={{ fontSize: '1.2rem', fontWeight: 700 }}>SINYAL PRO</h2>
        </div>
        <nav style={{ display: 'grid', gap: '0.5rem', marginTop: '1rem' }}>
          <button onClick={() => setActiveTab('signals')} className="btn" style={{ background: activeTab === 'signals' ? 'rgba(255,255,255,0.1)' : 'transparent', color: 'white', justifyContent: 'flex-start' }}>
            <BarChart3 size={18} /> Market Signals
          </button>
          <button onClick={() => setActiveTab('gateway')} className="btn" style={{ background: activeTab === 'gateway' ? 'rgba(255,255,255,0.1)' : 'transparent', color: 'white', justifyContent: 'flex-start' }}>
            <Smartphone size={18} /> WA Gateway
          </button>
          <button onClick={() => setActiveTab('history')} className="btn" style={{ background: activeTab === 'history' ? 'rgba(255,255,255,0.1)' : 'transparent', color: 'white', justifyContent: 'flex-start' }}>
            <History size={18} /> Activity Log
          </button>
        </nav>
        <div style={{ marginTop: 'auto' }}>
          <button className="btn" style={{ color: 'rgba(255,255,255,0.6)', justifyContent: 'flex-start' }}>
            <LogOut size={18} /> Logout
          </button>
        </div>
      </aside>

      <main className="main-content">
        <AnimatePresence>
          {notif && (
            <motion.div initial={{ y: -50, opacity: 0 }} animate={{ y: 20, opacity: 1 }} exit={{ y: -50, opacity: 0 }}
              style={{ position: 'fixed', top: 0, right: '40%', zIndex: 1000, background: notif.type === 'success' ? '#10b981' : '#ef4444', color: 'white', padding: '12px 24px', borderRadius: '12px', boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.1)' }}>
              {notif.type === 'success' ? <CheckCircle2 size={18} /> : <AlertCircle size={18} />} {notif.msg}
            </motion.div>
          )}
        </AnimatePresence>

        <div className="vibrant-header">
          <div style={{ position: 'relative', zIndex: 1 }}>
            <h1 style={{ fontSize: '1.8rem' }}>Welcome Back, Admin</h1>
            <p style={{ opacity: 0.8 }}>Sistem Dashboard Monitor Sinyal Saham Indonesia</p>
          </div>
        </div>

        {activeTab === 'signals' && (
          <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="fade-in">
            <div className="grid-stats">
              {stats.map(s => (
                <div key={s.label} className="card" style={{ display: 'flex', alignItems: 'center', gap: '15px' }}>
                  <div style={{ background: s.color + '20', padding: '12px', borderRadius: '12px' }}>
                    <s.icon color={s.color} size={28} />
                  </div>
                  <div>
                    <label style={{ display: 'block', fontSize: '0.85rem', color: 'var(--text-muted)' }}>{s.label}</label>
                    <b style={{ fontSize: '1.4rem' }}>{s.value}</b>
                  </div>
                </div>
              ))}
            </div>
            <div className="card">
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.5rem' }}>
                <h3>Active Signals</h3>
                <div style={{ display: 'flex', gap: '10px' }}>
                  <div style={{ position: 'relative' }}>
                    <Search size={16} style={{ position: 'absolute', left: '10px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                    <input style={{ paddingLeft: '35px', height: '40px', width: '200px' }} placeholder="Search Stock..." />
                  </div>
                  <button className="btn btn-primary"><Plus size={18} /> New Signal</button>
                </div>
              </div>
              <div className="table-container">
                <table>
                  <thead>
                    <tr>
                      <th>Stock Code</th>
                      <th>Type</th>
                      <th>Prices</th>
                      <th>WA Blast Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {signals.map(s => (
                      <tr key={s.id}>
                        <td><b style={{ fontSize: '1.1rem' }}>{s.stock_code}</b><br /><span style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>{s.title}</span></td>
                        <td><span className={`badge ${s.signal_type === 'Buy' ? 'badge-bull' : 'badge-bear'}`}>{s.signal_type}</span></td>
                        <td>
                          <div style={{ fontSize: '0.85rem' }}>
                            Entry: <b>{s.entry_price}</b><br />
                            TP: <span style={{ color: 'var(--bull)' }}>{s.take_profit}</span> | SL: <span style={{ color: 'var(--bear)' }}>{s.stop_loss}</span>
                          </div>
                        </td>
                        <td>
                          {s.wa_blasted_at ? (
                            <span style={{ color: 'var(--bull)', display: 'flex', alignItems: 'center', gap: '4px', fontSize: '0.8rem' }}>
                              <CheckCircle2 size={14} /> Sent at {s.wa_blasted_at}
                            </span>
                          ) : (
                            <span style={{ color: 'var(--text-muted)', fontSize: '0.8rem' }}>Not sent yet</span>
                          )}
                        </td>
                        <td>
                          <div style={{ display: 'flex', gap: '8px' }}>
                            <button className="btn btn-secondary" style={{ padding: '8px' }} onClick={() => handleWaBlast(s.id)} title="Send WA Blast"><Send size={16} /></button>
                            <button className="btn" style={{ padding: '8px', color: 'var(--bear)', background: '#fee2e2' }}><Trash2 size={16} /></button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          </motion.div>
        )}

        {activeTab === 'gateway' && (
          <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} className="card fade-in">
            <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '1.5rem' }}>
              <Smartphone color="var(--primary)" />
              <h2>WA Gateway Control Panel</h2>
            </div>
            <div style={{ background: '#fef3c7', padding: '1rem', borderRadius: '12px', border: '1px solid #f59e0b', marginBottom: '2rem', display: 'flex', gap: '15px' }}>
              <AlertCircle color="#b45309" size={24} />
              <p style={{ color: '#b45309' }}><b>Tips Memperbaiki Blast Gagal:</b> Pastikan Session ID di dashboard Fonnte/Alima dalam status <b>Connected</b>. Gunakan format internasional (628xxxx) untuk nomor client.</p>
            </div>
            <div className="grid-stats">
              <div className="card">
                <label className="input-label">Gateway Base URL</label>
                <input defaultValue="https://hubku.cuanholic.com" />
              </div>
              <div className="card">
                <label className="input-label">Session ID</label>
                <input defaultValue="wa628995295781" />
              </div>
              <div className="card">
                <label className="input-label">API Key</label>
                <input type="password" value="************************" />
              </div>
            </div>
            <div style={{ display: 'flex', gap: '15px', marginTop: '1rem' }}>
              <button className="btn btn-primary" onClick={() => showNotif('Testing Gateway Connection...', 'success')}>Test Connection</button>
            </div>
          </motion.div>
        )}
      </main>
    </div>
  );
};

export default App;
