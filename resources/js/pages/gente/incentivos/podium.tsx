import { useState } from 'react';

interface IncentivoRow {
    id: number;
    mes: string | null;
    cedula: string | null;
    nombre: string | null;
    cargo: string | null;
    indicador_1: string | null;
    pilar_1: string | null;
    total_1: string | null;
    meta_1: string | null;
    indicador_2: string | null;
    pilar_2: string | null;
    total_2: string | null;
    meta_2: string | null;
    indicador_3: string | null;
    pilar_3: string | null;
    total_3: string | null;
    meta_3: string | null;
    podium: string | null;
    valor_indicador_1: string | null;
    valor_indicador_2: string | null;
    valor_indicador_3: string | null;
    total_4: string | null;
    meta_4: string | null;
    colaborador: { id: number; nombres: string; apellidos: string; cedula: string } | null;
}

interface Props {
    incentivos: IncentivoRow[];
}

// ── helpers ────────────────────────────────────────────────────────────────

function n(v: string | null | undefined): number {
    const x = parseFloat(v ?? '');
    return isNaN(x) ? 0 : x;
}

function fmt(v: string | null | undefined): string {
    if (!v && v !== '0') return '—';
    const x = parseFloat(v ?? '');
    return isNaN(x) ? (v ?? '—') : x.toLocaleString('es-CO', { maximumFractionDigits: 1 });
}

/** Puntaje final: usa total_4 si existe, si no suma total_1+2+3 */
function score(item: IncentivoRow): number {
    if (n(item.total_4) > 0) return n(item.total_4);
    return n(item.total_1) + n(item.total_2) + n(item.total_3);
}

/** % de cumplimiento: valor vs meta. Si no hay valor_indicador usa total vs meta */
function pctCumplimiento(
    valor: string | null,
    meta: string | null,
    total: string | null,
    metaFallback: string | null,
): number | null {
    const v = n(valor) || n(total);
    const m = n(meta) || n(metaFallback);
    if (v === 0 || m === 0) return null;
    return Math.round((v / m) * 100);
}

function initials(nombre: string): string {
    return nombre.split(' ').filter(Boolean).slice(0, 2).map((p) => p[0].toUpperCase()).join('');
}

function nombreCompleto(item: IncentivoRow): string {
    if (item.colaborador) return `${item.colaborador.nombres} ${item.colaborador.apellidos}`;
    return item.nombre ?? '—';
}

// ── Anillo SVG ─────────────────────────────────────────────────────────────

function Ring({ pct, color, size = 52 }: { pct: number | null; color: string; size?: number }) {
    const r = (size - 8) / 2;
    const circ = 2 * Math.PI * r;
    const filled = pct !== null ? Math.min(1, pct / 100) * circ : 0;
    const cumple = pct !== null && pct >= 100;
    return (
        <div className="relative flex items-center justify-center" style={{ width: size, height: size }}>
            <svg width={size} height={size} className="-rotate-90">
                <circle cx={size / 2} cy={size / 2} r={r} fill="none" stroke="#e2e8f0" strokeWidth={5} />
                <circle
                    cx={size / 2} cy={size / 2} r={r} fill="none"
                    stroke={cumple ? '#22c55e' : color}
                    strokeWidth={5}
                    strokeDasharray={`${filled} ${circ}`}
                    strokeLinecap="round"
                    style={{ transition: 'stroke-dasharray .5s ease' }}
                />
            </svg>
            <span className="absolute text-[10px] font-bold leading-none" style={{ color: cumple ? '#16a34a' : color }}>
                {pct !== null ? `${pct}%` : '—'}
            </span>
        </div>
    );
}

// ── Tarjeta de pódium ──────────────────────────────────────────────────────

const RANK_STYLE = {
    1: { medal: '🥇', avatar: 'bg-yellow-100 text-yellow-700', border: 'border-yellow-300', pts: 'text-yellow-600 bg-yellow-50', ring: '#eab308', glow: 'ring-2 ring-yellow-300 shadow-xl', w: 'w-full sm:w-56', mt: '' },
    2: { medal: '🥈', avatar: 'bg-blue-100 text-blue-700',   border: 'border-blue-200',   pts: 'text-blue-600 bg-blue-50',     ring: '#3b82f6', glow: 'ring-2 ring-blue-200 shadow-lg',   w: 'w-full sm:w-48', mt: 'sm:mt-6' },
    3: { medal: '🥉', avatar: 'bg-orange-100 text-orange-700', border: 'border-orange-200', pts: 'text-orange-600 bg-orange-50', ring: '#f97316', glow: 'ring-2 ring-orange-200 shadow-lg',  w: 'w-full sm:w-48', mt: 'sm:mt-6' },
} as const;

function PodiumCard({ item, rank, active, onClick }: {
    item: IncentivoRow; rank: 1 | 2 | 3; active: boolean; onClick: () => void;
}) {
    const s = RANK_STYLE[rank];
    const nombre = nombreCompleto(item);
    const ini = initials(nombre);
    const pts = score(item);

    // Siempre mostrar los 3 anillos usando total/meta como fallback
    const anillos = [
        { label: item.indicador_1 ?? 'Ind. 1', v: item.valor_indicador_1, m: item.meta_1, vf: item.total_1, mf: item.meta_1 },
        { label: item.indicador_2 ?? 'Ind. 2', v: item.valor_indicador_2, m: item.meta_2, vf: item.total_2, mf: item.meta_2 },
        { label: item.indicador_3 ?? 'Ind. 3', v: item.valor_indicador_3, m: item.meta_3, vf: item.total_3, mf: item.meta_3 },
    ].filter((a) => n(a.v) > 0 || n(a.vf) > 0 || n(a.mf) > 0);

    return (
        <button
            onClick={onClick}
            className={`relative flex flex-col items-center gap-3 rounded-2xl border-2 bg-white p-5 cursor-pointer
                text-left transition-all duration-300 hover:scale-105
                ${s.border} ${s.w} ${s.mt}
                ${active ? s.glow : 'shadow-md hover:shadow-xl'}`}
        >
            <span className="absolute -top-4 text-2xl">{s.medal}</span>

            {/* Avatar */}
            <div className={`flex h-14 w-14 items-center justify-center rounded-full text-lg font-bold ${s.avatar}`}>
                {ini}
            </div>

            {/* Nombre */}
            <div className="text-center">
                <p className="text-sm font-bold text-slate-800 leading-tight">{nombre}</p>
                <p className="text-[11px] text-slate-400 mt-0.5">{item.cargo ?? ''}</p>
            </div>

            {/* Puntaje */}
            <div className={`rounded-full px-4 py-1 ${s.pts}`}>
                <span className="text-xl font-black">{pts > 0 ? pts.toLocaleString('es-CO', { maximumFractionDigits: 1 }) : '—'}</span>
                <span className="text-xs ml-1">pts</span>
            </div>

            {/* Anillos — siempre visibles */}
            {anillos.length > 0 && (
                <div className="flex gap-2 flex-wrap justify-center">
                    {anillos.map((a, i) => (
                        <div key={i} className="flex flex-col items-center gap-1">
                            <Ring
                                pct={pctCumplimiento(a.v, a.m, a.vf, a.mf)}
                                color={s.ring}
                                size={rank === 1 ? 52 : 44}
                            />
                            <span
                                className="text-[9px] text-slate-400 text-center leading-tight"
                                style={{ maxWidth: 44 }}
                                title={a.label}
                            >
                                {a.label.length > 10 ? a.label.slice(0, 10) + '…' : a.label}
                            </span>
                        </div>
                    ))}
                </div>
            )}
        </button>
    );
}

// ── Detalle al hacer click ─────────────────────────────────────────────────

function DetalleCard({ item }: { item: IncentivoRow }) {
    const nombre = nombreCompleto(item);
    const filas = [
        { label: item.indicador_1, pilar: item.pilar_1, valor: item.valor_indicador_1, total: item.total_1, meta: item.meta_1 },
        { label: item.indicador_2, pilar: item.pilar_2, valor: item.valor_indicador_2, total: item.total_2, meta: item.meta_2 },
        { label: item.indicador_3, pilar: item.pilar_3, valor: item.valor_indicador_3, total: item.total_3, meta: item.meta_3 },
    ];

    return (
        <div className="rounded-2xl border border-blue-200 bg-blue-50/60 p-5 shadow-inner animate-in fade-in duration-200">
            <div className="mb-4 flex items-start justify-between gap-2">
                <div>
                    <p className="font-bold text-slate-800">{nombre}</p>
                    <p className="text-xs text-slate-400">{item.cargo} · Mes: {item.mes}</p>
                </div>
                <div className="text-right">
                    <p className="text-2xl font-black text-blue-600">
                        {fmt(item.total_4 && n(item.total_4) > 0 ? item.total_4 : String(score(item)))}
                        <span className="text-sm font-normal ml-1 text-blue-400">pts</span>
                    </p>
                    {item.meta_4 && <p className="text-xs text-slate-400">Meta: {fmt(item.meta_4)}</p>}
                </div>
            </div>

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                {filas.map((f, i) => {
                    const v = n(f.valor) || n(f.total);
                    const m = n(f.meta);
                    const p = v > 0 && m > 0 ? Math.round((v / m) * 100) : null;
                    const cumple = p !== null && p >= 100;
                    if (!f.label && v === 0) return null;
                    return (
                        <div key={i} className="rounded-xl bg-white border border-blue-100 p-4 shadow-sm">
                            <p className="text-xs font-semibold text-slate-500 mb-1 line-clamp-2" title={f.label ?? ''}>
                                {f.label ?? `Indicador ${i + 1}`}
                            </p>
                            {f.pilar && (
                                <span className="inline-block rounded-full bg-blue-100 px-2 py-0.5 text-[10px] text-blue-700 mb-2">
                                    {f.pilar}
                                </span>
                            )}
                            <div className="flex items-end justify-between gap-2">
                                <div className="flex flex-col">
                                    <span className="text-xl font-black text-slate-700">
                                        {fmt(f.valor && n(f.valor) > 0 ? f.valor : f.total)}
                                    </span>
                                    {f.meta && <span className="text-[10px] text-slate-400">Meta: {fmt(f.meta)}</span>}
                                </div>
                                <Ring pct={p} color={cumple ? '#22c55e' : '#3b82f6'} size={48} />
                            </div>
                        </div>
                    );
                })}
            </div>

            {item.podium && (
                <p className="mt-3 text-center text-xs text-slate-400">
                    Posición pódium declarada: <span className="font-semibold text-blue-600">{item.podium}</span>
                </p>
            )}
        </div>
    );
}

// ── Componente principal ───────────────────────────────────────────────────

export function PodiumIncentivos({ incentivos }: Props) {
    const [seleccionado, setSeleccionado] = useState<number | null>(null);

    // Ordenar por score desc
    const sorted = [...incentivos].sort((a, b) => score(b) - score(a));
    const top3 = sorted.slice(0, 3);
    const resto = sorted.slice(3);

    if (top3.length === 0) return null;

    const itemSel = incentivos.find((i) => i.id === seleccionado) ?? null;
    const mesLabel = top3[0]?.mes ?? '';

    // Orden visual: 2° | 1° | 3°
    const visual: { rank: 1 | 2 | 3; item: IncentivoRow }[] = (
        [
            top3[1] ? { rank: 2 as const, item: top3[1] } : null,
            top3[0] ? { rank: 1 as const, item: top3[0] } : null,
            top3[2] ? { rank: 3 as const, item: top3[2] } : null,
        ] as ({ rank: 1 | 2 | 3; item: IncentivoRow } | null)[]
    ).filter(Boolean) as { rank: 1 | 2 | 3; item: IncentivoRow }[];

    return (
        <div className="rounded-2xl border border-blue-100 bg-gradient-to-b from-blue-50/80 to-white p-6 shadow-sm">
            {/* Título */}
            <div className="mb-6 text-center">
                {mesLabel && (
                    <span className="mb-2 inline-block rounded-full border border-blue-200 bg-white px-3 py-0.5 text-xs text-slate-500 uppercase tracking-wide">
                        {mesLabel}
                    </span>
                )}
                <h2 className="text-2xl font-black text-slate-800">🏆 Mejores del Mes</h2>
                <p className="mt-1 text-xs text-slate-400">Haz clic en una tarjeta para ver el detalle completo</p>
            </div>

            {/* Pódium */}
            <div className="flex flex-col items-center gap-4 pt-6 pb-4 sm:flex-row sm:items-end sm:justify-center">
                {visual.map(({ rank, item }) => (
                    <PodiumCard
                        key={item.id}
                        item={item}
                        rank={rank}
                        active={seleccionado === item.id}
                        onClick={() => setSeleccionado(seleccionado === item.id ? null : item.id)}
                    />
                ))}
            </div>

            {/* Detalle expandido */}
            {itemSel && (
                <div className="mb-4">
                    <DetalleCard item={itemSel} />
                </div>
            )}

            {/* Clasificación 4+ eliminada */}
        </div>
    );
}
