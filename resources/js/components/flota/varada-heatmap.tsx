import 'leaflet/dist/leaflet.css';
import { CircleMarker, MapContainer, Popup, TileLayer } from 'react-leaflet';

export interface PuntoMapa {
    lat: number;
    lng: number;
    peso: number;
    lugar: string;
    dias_fs_total: number;
    abiertas: number;
}

// Centro por defecto: CD Pasto (Nariño), donde opera esta flota.
const CENTRO_DEFECTO: [number, number] = [1.2136, -77.2811];

const RADIO_MIN = 10;
const RADIO_MAX = 42;

export function VaradaHeatmap({ puntos }: { puntos: PuntoMapa[] }) {
    const centro: [number, number] =
        puntos.length > 0
            ? [puntos.reduce((s, p) => s + p.lat, 0) / puntos.length, puntos.reduce((s, p) => s + p.lng, 0) / puntos.length]
            : CENTRO_DEFECTO;

    const maxPeso = Math.max(1, ...puntos.map((p) => p.peso));

    return (
        <MapContainer center={centro} zoom={9} scrollWheelZoom={false} style={{ height: 420, width: '100%', borderRadius: '0.5rem', zIndex: 0 }}>
            <TileLayer
                attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
            />
            {puntos.map((punto, index) => {
                const radio = RADIO_MIN + (RADIO_MAX - RADIO_MIN) * Math.sqrt(punto.peso / maxPeso);

                return (
                    <CircleMarker
                        key={`${punto.lat}-${punto.lng}-${index}`}
                        center={[punto.lat, punto.lng]}
                        radius={radio}
                        pathOptions={{ color: '#ffffff', weight: 2, fillColor: '#2B6CB0', fillOpacity: 0.65 }}
                    >
                        <Popup>
                            <div className="flex flex-col gap-1 text-sm">
                                <span className="font-bold uppercase">{punto.lugar}</span>
                                <span>Varadas: {punto.peso}</span>
                                <span>Días FS acumulados: {punto.dias_fs_total}</span>
                                {punto.abiertas > 0 && <span className="text-destructive">Abiertas: {punto.abiertas}</span>}
                            </div>
                        </Popup>
                    </CircleMarker>
                );
            })}
        </MapContainer>
    );
}
