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

function Ring({ pct, color, size = 52, fontSize = '10px' }: { pct: number | null; color: string; size?: number; fontSize?: string }) {
    const r = (size - 8) / 2;
    const circ = 2 * Math.PI * r;
    const filled = pct !== null ? Math.min(1, pct / 100) * circ : 0;
    const cumple = pct !== null && pct >= 100;
    return (
        <div className="relative flex items-center justify-center shrink-0" style={{ width: size, height: size }}>
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
            <span className="absolute font-bold leading-none" style={{ fontSize, color: cumple ? '#16a34a' : color }}>
                {pct !== null ? `${pct}%` : '—'}
            </span>
        </div>
    );
}

// ── Tarjeta de pódium ──────────────────────────────────────────────────────

const RANK_STYLE = {
    1: {
        medal: '🥇',
        avatar: 'bg-yellow-100 text-yellow-700',
        border: 'border-yellow-300',
        pts: 'text-yellow-600 bg-yellow-50',
        ring: '#eab308',
        glow: 'ring-2 ring-yellow-300 shadow-xl',
        w: 'w-full xs:w-[calc(50%-0.5rem)] sm:w-52 md:w-56',
        mt: '',
        avatarSize: 'h-12 w-12 sm:h-14 sm:w-14',
        avatarText: 'text-base sm:text-lg',
        ptsText: 'text-lg sm:text-xl',
        pCard: 'p-3.5 sm:p-4 md:p-5',
        gapCard: 'gap-2.5 sm:gap-3',
    },
    2: {
        medal: '🥈',
        avatar: 'bg-blue-100 text-blue-700',
        border: 'border-blue-200',
        pts: 'text-blue-600 bg-blue-50',
        ring: '#3b82f6',
        glow: 'ring-2 ring-blue-200 shadow-lg',
        w: 'w-full xs:w-[calc(50%-0.5rem)] sm:w-44 md:w-48',
        mt: 'xs:mt-0 sm:mt-4 md:mt-6',
        avatarSize: 'h-11 w-11 sm:h-12 sm:w-12',
        avatarText: 'text-sm sm:text-base',
        ptsText: 'text-base sm:text-lg',
        pCard: 'p-3 sm:p-3.5 md:p-4',
        gapCard: 'gap-2 sm:gap-2.5',
    },
    3: {
        medal: '🥉',
        avatar: 'bg-orange-100 text-orange-700',
        border: 'border-orange-200',
        pts: 'text-orange-600 bg-orange-50',
        ring: '#f97316',
        glow: 'ring-2 ring-orange-200 shadow-lg',
        w: 'w-full xs:w-[calc(50%-0.5rem)] sm:w-44 md:w-48',
        mt: 'xs:mt-0 sm:mt-4 md:mt-6',
        avatarSize: 'h-11 w-11 sm:h-12 sm:w-12',
        avatarText: 'text-sm sm:text-base',
        ptsText: 'text-base sm:text-lg',
        pCard: 'p-3 sm:p-3.5 md:p-4',
        gapCard: 'gap-2 sm:gap-2.5',
    },
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
            className={`relative flex flex-col items-center ${s.gapCard} rounded-2xl border-2 bg-white cursor-pointer
                text-left transition-all duration-300 hover:scale-[1.02] sm:hover:scale-105
                ${s.border} ${s.w} ${s.mt} ${s.pCard}
                ${active ? s.glow : 'shadow-md hover:shadow-xl'}`}
        >
            <span className="absolute -top-3 sm:-top-4 text-xl sm:text-2xl leading-none">{s.medal}</span>

            {/* Avatar */}
            <div className={`flex ${s.avatarSize} items-center justify-center rounded-full ${s.avatarText} font-bold ${s.avatar}`}>
                {ini}
            </div>

            {/* Nombre */}
            <div className="text-center w-full min-w-0">
                <p className={`text-sm font-bold text-slate-800 leading-tight truncate ${rank === 1 ? 'sm:text-[15px]' : ''}`}>{nombre}</p>
                <p className="text-[11px] sm:text-xs text-slate-400 mt-0.5 leading-tight line-clamp-2 min-h-[2em]">{item.cargo ?? ''}</p>
            </div>

            {/* Puntaje */}
            <div className={`rounded-full px-3 sm:px-4 py-0.5 sm:py-1 ${s.pts}`}>
                <span className={`${s.ptsText} font-black`}>
                    {pts > 0 ? pts.toLocaleString('es-CO', { maximumFractionDigits: 1 }) : '—'}
                </span>
                <span className="text-[11px] sm:text-xs ml-0.5 sm:ml-1">pts</span>
            </div>

            {/* Anillos — siempre visibles */}
            {anillos.length > 0 && (
                <div className="flex gap-1.5 sm:gap-2 flex-wrap justify-center w-full">
                    {anillos.map((a, i) => {
                        const ringSize = rank === 1 ? 46 : 40;
                        const ringFont = rank === 1 ? '9px' : '8px';
                        return (
                            <div key={i} className="flex flex-col items-center gap-0.5 sm:gap-1 max-w-[52px]">
                                <Ring
                                    pct={pctCumplimiento(a.v, a.m, a.vf, a.mf)}
                                    color={s.ring}
                                    size={ringSize}
                                    fontSize={ringFont}
                                />
                                <span
                                    className="text-[9px] sm:text-[10px] text-slate-400 text-center leading-tight break-words w-full"
                                    title={a.label}
                                >
                                    {a.label.length > 10 ? a.label.slice(0, 10) + '…' : a.label}
                                </span>
                            </div>
                        );
                    })}
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
        <div className="rounded-2xl border border-blue-200 bg-blue-50/60 p-3.5 sm:p-4 md:p-5 shadow-inner animate-in fade-in duration-200">
            <div className="mb-3 sm:mb-4 flex flex-col sm:flex-row sm:items-start justify-between gap-2">
                <div className="min-w-0">
                    <p className="font-bold text-slate-800 truncate">{nombre}</p>
                    <p className="text-xs text-slate-400">
                        {item.cargo}{item.cargo && item.mes ? ' · ' : ''}Mes: {item.mes}
                    </p>
                </div>
                <div className="text-left sm:text-right">
                    <p className="text-xl sm:text-2xl font-black text-blue-600 leading-tight">
                        {fmt(item.total_4 && n(item.total_4) > 0 ? item.total_4 : String(score(item)))}
                        <span className="text-xs sm:text-sm font-normal ml-1 text-blue-400">pts</span>
                    </p>
                    {item.meta_4 && <p className="text-xs text-slate-400">Meta: {fmt(item.meta_4)}</p>}
                </div>
            </div>

            <div className="grid grid-cols-1 gap-2.5 sm:gap-3 sm:grid-cols-2 md:grid-cols-3">
                {filas.map((f, i) => {
                    const v = n(f.valor) || n(f.total);
                    const m = n(f.meta);
                    const p = v > 0 && m > 0 ? Math.round((v / m) * 100) : null;
                    const cumple = p !== null && p >= 100;
                    if (!f.label && v === 0) return null;
                    return (
                        <div key={i} className="rounded-xl bg-white border border-blue-100 p-3 sm:p-4 shadow-sm">
                            <p className="text-xs font-semibold text-slate-500 mb-1 line-clamp-2 min-h-[2em]" title={f.label ?? ''}>
                                {f.label ?? `Indicador ${i + 1}`}
                            </p>
                            <div className="flex flex-wrap gap-1 mb-2">
                                {f.pilar && (
                                    <span className="inline-block rounded-full bg-blue-100 px-2 py-0.5 text-[10px] text-blue-700">
                                        {f.pilar}
                                    </span>
                                )}
                                {f.valor && n(f.valor) !== 0 && (
                                    <span className="inline-block rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] text-emerald-700">
                                        V: {fmt(f.valor)}
                                    </span>
                                )}
                            </div>
                            <div className="flex items-end justify-between gap-2">
                                <div className="flex flex-col min-w-0">
                                    <span className="text-lg sm:text-xl font-black text-slate-700 leading-tight truncate">
                                        {fmt(f.valor && n(f.valor) > 0 ? f.valor : f.total)}
                                    </span>
                                    {f.meta && <span className="text-[10px] sm:text-xs text-slate-400">Meta: {fmt(f.meta)}</span>}
                                </div>
                                <Ring pct={p} color={cumple ? '#22c55e' : '#3b82f6'} size={44} fontSize="9px" />
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
        <div className="rounded-2xl border border-blue-100 bg-gradient-to-b from-blue-50/80 to-white p-3.5 sm:p-4 md:p-5 lg:p-6 shadow-sm">
            {/* Título */}
            <div className="mb-4 sm:mb-5 md:mb-6 text-center">
                {mesLabel && (
                    <span className="mb-1.5 sm:mb-2 inline-block rounded-full border border-blue-200 bg-white px-2.5 sm:px-3 py-0.5 text-[11px] sm:text-xs text-slate-500 uppercase tracking-wide">
                        {mesLabel}
                    </span>
                )}
                <h2 className="text-xl sm:text-2xl font-black text-slate-800 leading-tight">🏆 Mejores del Mes</h2>
                <p className="mt-1 text-[11px] sm:text-xs text-slate-400">Haz clic en una tarjeta para ver el detalle completo</p>
            </div>

            {/* Pódium */}
            <div className="flex flex-col items-center gap-3 xs:flex-row xs:flex-wrap xs:justify-center xs:items-start sm:items-end sm:justify-center pt-4 sm:pt-6 pb-2 sm:pb-4">
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
                <div className="mb-3 sm:mb-4">
                    <DetalleCard item={itemSel} />
                </div>
            )}

            {/* Clasificación 4+ */}
            {resto.length > 0 && (
                <div className="mt-3 sm:mt-4">
                    <p className="mb-1.5 sm:mb-2 text-[11px] sm:text-xs font-semibold text-slate-500 flex items-center gap-1">
                        <span>📋</span> Clasificación completa
                        <span className="ml-auto font-normal text-slate-400">({resto.length} colaboradores)</span>
                    </p>
                    <div className="flex flex-col gap-1 sm:gap-1.5">
                        {resto.map((item, idx) => {
                            const nombre = nombreCompleto(item);
                            const ini = initials(nombre);
                            const pts = score(item);
                            const isAct = seleccionado === item.id;

                            const mini = [
                                { v: item.valor_indicador_1, m: item.meta_1, vf: item.total_1 },
                                { v: item.valor_indicador_2, m: item.meta_2, vf: item.total_2 },
                                { v: item.valor_indicador_3, m: item.meta_3, vf: item.total_3 },
                            ].filter((x) => n(x.v) > 0 || n(x.vf) > 0);

                            return (
                                <button
                                    key={item.id}
                                    onClick={() => setSeleccionado(isAct ? null : item.id)}
                                    className={`flex items-center gap-2 sm:gap-3 rounded-xl border px-2.5 sm:px-4 py-2 sm:py-3 text-left
                                        cursor-pointer transition-all duration-200
                                        ${isAct ? 'border-blue-300 bg-blue-50 shadow-sm' : 'border-slate-100 bg-white hover:border-blue-200 hover:bg-blue-50/40'}`}
                                >
                                    <span className="w-5 shrink-0 text-center text-[11px] sm:text-xs font-bold text-slate-400">{idx + 4}</span>
                                    <div className="flex h-7 w-7 sm:h-8 sm:w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-[11px] sm:text-xs font-bold text-blue-700">
                                        {ini}
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-semibold text-slate-700">{nombre}</p>
                                        <p className="text-[10px] sm:text-[11px] text-slate-400 truncate">{item.cargo}</p>
                                    </div>
                                    <div className="hidden md:flex gap-2 sm:gap-3 shrink-0">
                                        {mini.map((x, i) => {
                                            const val = n(x.v) || n(x.vf);
                                            const met = n(x.m);
                                            const p = val > 0 && met > 0 ? Math.round((val / met) * 100) : null;
                                            const ok = p !== null && p >= 100;
                                            return (
                                                <span key={i} className={`text-[11px] sm:text-xs font-bold ${ok ? 'text-emerald-600' : 'text-rose-500'} whitespace-nowrap`}>
                                                    {val > 0 ? val.toLocaleString('es-CO', { maximumFractionDigits: 1 }) : '—'}
                                                </span>
                                            );
                                        })}
                                    </div>
                                    <span className="ml-1 sm:ml-3 shrink-0 text-sm sm:text-base font-black text-blue-600 whitespace-nowrap">
                                        {pts > 0 ? pts.toLocaleString('es-CO', { maximumFractionDigits: 1 }) : '—'}
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                </div>
            )}
        </div>
    );
}
