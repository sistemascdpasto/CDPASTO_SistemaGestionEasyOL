/**
 * Los 64 municipios del departamento de Nariño (Colombia) más el CD Pasto,
 * con coordenadas de su cabecera municipal (no del centroide del área
 * rural del municipio, que puede quedar a decenas de km del casco urbano).
 * Fuente: nodos place=city/town/village de OpenStreetMap dentro del
 * límite administrativo de Nariño, verificados contra fuentes
 * independientes para Pasto, Ipiales y Tumaco.
 *
 * Se usa en el formulario de "Control de Varadas" (Flota): al elegir un
 * lugar, se autocompletan latitud/longitud.
 */
export interface MunicipioNarino {
    lugar: string;
    latitud: number;
    longitud: number;
}

export const MUNICIPIOS_NARINO: MunicipioNarino[] = [
    { lugar: 'CD Pasto', latitud: 1.153621, longitud: -77.305217 },
    { lugar: 'Albán', latitud: 1.4744366, longitud: -77.0813409 },
    { lugar: 'Aldana', latitud: 0.8822547, longitud: -77.7003164 },
    { lugar: 'Ancuya', latitud: 1.2615284, longitud: -77.5147482 },
    { lugar: 'Arboleda', latitud: 1.5031876, longitud: -77.1357811 },
    { lugar: 'Barbacoas', latitud: 1.6741037, longitud: -78.1364951 },
    { lugar: 'Belén', latitud: 1.5961164, longitud: -77.0159361 },
    { lugar: 'Buesaco', latitud: 1.384101, longitud: -77.156619 },
    { lugar: 'Chachagüí', latitud: 1.360752, longitud: -77.2831744 },
    { lugar: 'Colón (Génova)', latitud: 1.6428515, longitud: -77.0189736 },
    { lugar: 'Consacá', latitud: 1.2082795, longitud: -77.4656149 },
    { lugar: 'Contadero', latitud: 0.9082681, longitud: -77.547381 },
    { lugar: 'Córdoba', latitud: 0.8540703, longitud: -77.5182592 },
    { lugar: 'Cuaspud (Carlosama)', latitud: 0.8639417, longitud: -77.7271476 },
    { lugar: 'Cumbal', latitud: 0.9092082, longitud: -77.788906 },
    { lugar: 'Cumbitara', latitud: 1.6473621, longitud: -77.578094 },
    { lugar: 'El Charco', latitud: 2.4798209, longitud: -78.110793 },
    { lugar: 'El Peñol', latitud: 1.454125, longitud: -77.4396657 },
    { lugar: 'El Rosario', latitud: 1.7426237, longitud: -77.3349654 },
    { lugar: 'El Tablón de Gómez', latitud: 1.4272265, longitud: -77.0976122 },
    { lugar: 'El Tambo', latitud: 1.4093115, longitud: -77.3923306 },
    { lugar: 'Francisco Pizarro (Salahonda)', latitud: 2.039908, longitud: -78.6581414 },
    { lugar: 'Funes', latitud: 0.9987301, longitud: -77.448542 },
    { lugar: 'Guachucal', latitud: 0.9573434, longitud: -77.7333581 },
    { lugar: 'Guaitarilla', latitud: 1.1305245, longitud: -77.5483421 },
    { lugar: 'Gualmatán', latitud: 0.9200211, longitud: -77.5661383 },
    { lugar: 'Iles', latitud: 0.9698295, longitud: -77.5201636 },
    { lugar: 'Imués', latitud: 1.0554687, longitud: -77.4958319 },
    { lugar: 'Ipiales', latitud: 0.8236501, longitud: -77.6348556 },
    { lugar: 'La Cruz', latitud: 1.6003354, longitud: -76.9711667 },
    { lugar: 'La Florida', latitud: 1.2986074, longitud: -77.4044845 },
    { lugar: 'La Llanada', latitud: 1.472721, longitud: -77.5802015 },
    { lugar: 'La Tola', latitud: 2.3995791, longitud: -78.1882925 },
    { lugar: 'La Unión', latitud: 1.5989976, longitud: -77.1305999 },
    { lugar: 'Leiva', latitud: 1.9356061, longitud: -77.3068188 },
    { lugar: 'Linares', latitud: 1.3516458, longitud: -77.5235169 },
    { lugar: 'Los Andes (Sotomayor)', latitud: 1.4932789, longitud: -77.5210552 },
    { lugar: 'Magüí Payán', latitud: 1.7665774, longitud: -78.1835713 },
    { lugar: 'Mallama (Piedrancha)', latitud: 1.1404576, longitud: -77.8643814 },
    { lugar: 'Mosquera', latitud: 2.507865, longitud: -78.4516461 },
    { lugar: 'Nariño', latitud: 1.2906755, longitud: -77.3578572 },
    { lugar: 'Olaya Herrera (Bocas de Satinga)', latitud: 2.348495, longitud: -78.3267125 },
    { lugar: 'Ospina', latitud: 1.0586533, longitud: -77.5656698 },
    { lugar: 'Pasto', latitud: 1.2140275, longitud: -77.2785096 },
    { lugar: 'Policarpa', latitud: 1.6286636, longitud: -77.4594651 },
    { lugar: 'Potosí', latitud: 0.8072344, longitud: -77.573045 },
    { lugar: 'Providencia', latitud: 1.2391932, longitud: -77.5973656 },
    { lugar: 'Puerres', latitud: 0.8855995, longitud: -77.5019248 },
    { lugar: 'Pupiales', latitud: 0.8707969, longitud: -77.6419358 },
    { lugar: 'Ricaurte', latitud: 1.213249, longitud: -77.9945007 },
    { lugar: 'Roberto Payán', latitud: 1.6967, longitud: -78.2453 },
    { lugar: 'Samaniego', latitud: 1.3389525, longitud: -77.5933795 },
    { lugar: 'San Bernardo', latitud: 1.5158752, longitud: -77.0457718 },
    { lugar: 'San Lorenzo', latitud: 1.5031548, longitud: -77.2153366 },
    { lugar: 'San Pablo', latitud: 1.6684528, longitud: -77.0109928 },
    { lugar: 'San Pedro de Cartago', latitud: 1.5524899, longitud: -77.1200173 },
    { lugar: 'Sandoná', latitud: 1.2863843, longitud: -77.4710722 },
    { lugar: 'Santa Bárbara (Iscuandé)', latitud: 2.4486619, longitud: -77.9797375 },
    { lugar: 'Santacruz (Guachavés)', latitud: 1.2226827, longitud: -77.6769685 },
    { lugar: 'Sapuyes', latitud: 1.0377201, longitud: -77.6209192 },
    { lugar: 'Taminango', latitud: 1.5696216, longitud: -77.2772963 },
    { lugar: 'Tangua', latitud: 1.0944024, longitud: -77.3948049 },
    { lugar: 'Tumaco', latitud: 1.8062887, longitud: -78.7649814 },
    { lugar: 'Túquerres', latitud: 1.0877024, longitud: -77.6188242 },
    { lugar: 'Yacuanquer', latitud: 1.1158819, longitud: -77.4017561 },
];
