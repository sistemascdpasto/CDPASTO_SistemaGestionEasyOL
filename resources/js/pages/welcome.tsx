import Aurora from '@/components/Aurora';
import ClickSpark from '@/components/ClickSpark';
import { CountUp } from '@/components/count-up';
import { GlowBorder } from '@/components/glow-border';
import GradientText from '@/components/GradientText';
import { Reveal } from '@/components/reveal';
import RotatingText from '@/components/RotatingText';
import { ShinyText } from '@/components/shiny-text';
import SplitText from '@/components/SplitText';
import { SpotlightCard } from '@/components/spotlight-card';
import TiltedCard from '@/components/TiltedCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { flattenSubmodules, modules } from '@/data/modules';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Beer,
    Building2,
    Calendar,
    ChevronDown,
    Globe,
    LayoutGrid,
    MapPin,
    ShieldCheck,
    ShoppingCart,
    Sparkles,
    Truck,
    type LucideIcon,
} from 'lucide-react';
import { useEffect, useState } from 'react';

interface Feature {
    icon: LucideIcon;
    label: string;
}

interface CompanyCardProps {
    icon: LucideIcon;
    accentColor: string;
    eyebrow: string;
    title: string;
    subtitle: string;
    description: string;
    features: Feature[];
}

function CompanyCard({ icon: Icon, accentColor, eyebrow, title, subtitle, description, features }: CompanyCardProps) {
    return (
        <SpotlightCard color={accentColor} className="group h-full rounded-lg">
            <Card className="relative flex h-full flex-col gap-6 overflow-hidden p-8 transition-all duration-300 group-hover:-translate-y-1 group-hover:shadow-lg">
                <span
                    className="absolute inset-x-0 top-0 h-1.5 opacity-80 transition-opacity duration-300 group-hover:opacity-100"
                    style={{ backgroundColor: accentColor }}
                />

                <div
                    className="flex size-14 items-center justify-center rounded-2xl transition-transform duration-300 group-hover:scale-105 group-hover:rotate-3"
                    style={{ backgroundColor: `${accentColor}1a`, color: accentColor }}
                >
                    <Icon className="size-7" />
                </div>

                <div>
                    <p className="text-sm font-medium tracking-wide uppercase" style={{ color: accentColor }}>
                        {eyebrow}
                    </p>
                    <h3 className="mt-1 text-2xl font-semibold text-foreground">{title}</h3>
                    <p className="text-sm text-muted-foreground">{subtitle}</p>
                </div>

                <p className="flex-1 leading-relaxed text-muted-foreground">{description}</p>

                <div className="flex flex-wrap gap-2">
                    {features.map((feature) => (
                        <Badge key={feature.label} variant="secondary" className="gap-1.5 px-3 py-1 font-normal">
                            <feature.icon className="size-3.5" />
                            {feature.label}
                        </Badge>
                    ))}
                </div>
            </Card>
        </SpotlightCard>
    );
}

function useIsScrolled(threshold = 8) {
    const [isScrolled, setIsScrolled] = useState(false);

    useEffect(() => {
        const onScroll = () => setIsScrolled(window.scrollY > threshold);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    }, [threshold]);

    return isScrolled;
}

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;
    const isScrolled = useIsScrolled();

    // Colores extraídos directamente del banner Bavaria | Easy Logística
    // (public/images/Banner easy.png): azul y dorado del colibrí como
    // acentos primarios, rojo Bavaria sin tocar (marca de un tercero).
    const brandBlueDark = '#004985';
    const brandBlue = '#0065B9';
    const bavaria = '#D4102A';
    const gold = '#FDC30D';

    const totalProcesses = modules.reduce((total, module) => total + flattenSubmodules(module.submodules).length, 0);

    return (
        <>
            <Head title="EASY LOGÍSTICA S.A.S. — Sistema Integral de Gestión">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>

            <div className="min-h-screen overflow-x-hidden bg-background text-foreground">
                <header
                    className={`sticky top-0 z-50 border-b transition-all duration-300 ${
                        isScrolled ? 'border-border bg-background/80 shadow-sm backdrop-blur-md' : 'border-transparent bg-transparent'
                    }`}
                >
                    <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-4 gap-y-3 px-6 py-4">
                        <div className="flex items-center gap-2.5">
                            <div
                                className="flex size-9 shrink-0 items-center justify-center rounded-lg text-white shadow-sm"
                                style={{ backgroundImage: `linear-gradient(135deg, ${brandBlue}, ${brandBlueDark})` }}
                            >
                                <Building2 className="size-5" />
                            </div>
                            <span className="text-lg font-semibold tracking-tight whitespace-nowrap">EASY LOGÍSTICA S.A.S.</span>
                        </div>

                        <nav className="flex items-center gap-2 sm:gap-3">
                            {auth.user ? (
                                <Button size="sm" asChild>
                                    <Link href={route('dashboard')}>Ir al Dashboard</Link>
                                </Button>
                            ) : (
                                <>

                                    <Button size="sm" asChild>
                                        <Link href={route('login')}>Iniciar sesión</Link>
                                    </Button>
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <main>
                    <section className="relative overflow-hidden">
                        <div className="pointer-events-none absolute inset-0 -z-10">
                            {/* Fondo real de React Bits (WebGL vía `ogl`), en vez del blob CSS
                                anterior — la paleta de marca (azul y dorado Easy Logística, rojo Bavaria)
                                como stops de color. Ver components.json -> registro @react-bits. */}
                            <div className="absolute inset-0 opacity-70 dark:opacity-60">
                                <Aurora colorStops={[brandBlue, gold, bavaria]} amplitude={1.6} blend={0.5} speed={1.1} />
                            </div>
                            <div className="bg-dot-grid absolute inset-0 opacity-30 [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,black,transparent)]" />
                            <div className="bg-grain absolute inset-0 opacity-[0.035] mix-blend-overlay" />
                            <div className="absolute inset-x-0 bottom-0 h-32 bg-gradient-to-b from-transparent to-background" />
                        </div>

                        <div className="mx-auto grid max-w-6xl items-center gap-12 px-6 py-16 lg:grid-cols-2 lg:py-24">
                            <Reveal className="flex flex-col items-start gap-6">
                                <div className="relative inline-flex">
                                    <div
                                        className="absolute -inset-px rounded-full opacity-70 blur-[3px]"
                                        style={{ backgroundImage: `linear-gradient(90deg, ${brandBlue}, ${gold}, ${bavaria})` }}
                                    />
                                    <Badge variant="secondary" className="relative gap-1.5 rounded-full border-0 bg-white px-3 py-1 font-normal">
                                        <ShieldCheck className="size-3.5" />
                                        Aliado logístico de Bavaria en Nariño
                                    </Badge>
                                </div>

                                <h1 className="text-4xl leading-tight font-bold tracking-tight text-balance sm:text-6xl">
                                    <SplitText
                                        text="Sistema Integral de Gestión"
                                        tag="span"
                                        className="inline"
                                        splitType="chars"
                                        textAlign="left"
                                        delay={18}
                                        duration={0.7}
                                    />{' '}
                                    <ShinyText color={brandBlue}>EASY LOGÍSTICA S.A.S.</ShinyText>
                                </h1>

                                <div className="flex flex-wrap items-center gap-2 text-xl font-semibold sm:text-2xl">
                                    <span className="text-muted-foreground">Control total de</span>
                                    <RotatingText
                                        texts={modules.map((module) => module.title)}
                                        mainClassName="rounded-lg px-3 py-1 text-white shadow-sm"
                                        style={{ backgroundImage: `linear-gradient(135deg, ${brandBlue}, ${brandBlueDark})` }}
                                        splitLevelClassName="overflow-hidden"
                                        staggerFrom="last"
                                        staggerDuration={0.02}
                                        rotationInterval={2200}
                                        transition={{ type: 'spring', damping: 28, stiffness: 350 }}
                                    />
                                </div>

                                <p className="text-lg leading-relaxed text-pretty text-muted-foreground">
                                    Plataforma interna para administrar los pilares operativos de EASY LOGÍSTICA S.A.S., empresa de servicios logísticos
                                    aliada de Bavaria en el departamento de Nariño: seguridad, reparto, gente y flota, todo en un solo lugar.
                                </p>

                                <div className="flex flex-wrap gap-3">
                                    {auth.user ? (
                                        <div className="inline-flex rounded-md">
                                            <ClickSpark sparkColor={brandBlue} sparkCount={10} sparkRadius={20} sparkSize={12}>
                                                <Button
                                                    size="lg"
                                                    className="shadow-[0_8px_30px_-10px_rgba(63,122,34,0.55)] transition-shadow duration-300 hover:shadow-[0_12px_36px_-8px_rgba(63,122,34,0.7)]"
                                                    asChild
                                                >
                                                    <Link href={route('dashboard')}>Ir al Dashboard</Link>
                                                </Button>
                                            </ClickSpark>
                                        </div>
                                    ) : (
                                        <div className="inline-flex rounded-md">
                                            <ClickSpark sparkColor={brandBlue} sparkCount={10} sparkRadius={20} sparkSize={12}>
                                                <Button
                                                    size="lg"
                                                    className="group shadow-[0_8px_30px_-10px_rgba(63,122,34,0.55)] transition-shadow duration-300 hover:shadow-[0_12px_36px_-8px_rgba(63,122,34,0.7)]"
                                                    asChild
                                                >
                                                    <Link href={route('login')}>
                                                        Iniciar sesión
                                                        <ArrowRight className="size-4 transition-transform duration-300 group-hover:translate-x-0.5" />
                                                    </Link>
                                                </Button>
                                            </ClickSpark>
                                        </div>
                                    )}
                                </div>

                                <div className="mt-2 flex flex-wrap items-center gap-x-8 gap-y-4">
                                    <div>
                                        <p className="text-3xl font-bold tracking-tight" style={{ color: brandBlue }}>
                                            <CountUp end={modules.length} />
                                        </p>
                                        <p className="text-sm text-muted-foreground">Pilares operativos</p>
                                    </div>
                                    <div className="h-10 w-px bg-border" />
                                    <div>
                                        <p className="text-3xl font-bold tracking-tight" style={{ color: brandBlue }}>
                                            <CountUp end={totalProcesses} />
                                        </p>
                                        <p className="text-sm text-muted-foreground">Procesos digitalizados</p>
                                    </div>
                                    <div className="h-10 w-px bg-border" />
                                    <div>
                                        <p className="text-3xl font-bold tracking-tight" style={{ color: brandBlue }}>
                                            <CountUp end={135} suffix="+" />
                                        </p>
                                        <p className="text-sm text-muted-foreground">Años de trayectoria Bavaria</p>
                                    </div>
                                </div>
                            </Reveal>

                            <Reveal delay={150} className="relative">
                                <GlowBorder colors={[brandBlue, gold, bavaria]} className="animate-float" rounded="rounded-2xl">
                                    <div className="relative rounded-2xl bg-white p-6 shadow-2xl" style={{ animationDuration: '7s' }}>
                                        {/* Relación 2:1 real del logo (1774×887px) para que TiltedCard
                                            no recorte ni distorsione la imagen al aplicar object-cover. */}
                                        <div className="aspect-[2/1] w-full overflow-hidden rounded-lg">
                                            <TiltedCard
                                                imageSrc="/images/Banner easy.png"
                                                altText="EASY LOGÍSTICA S.A.S. — aliado logístico de Bavaria"
                                                containerWidth="100%"
                                                containerHeight="100%"
                                                imageWidth="100%"
                                                imageHeight="100%"
                                                rotateAmplitude={10}
                                                scaleOnHover={1.04}
                                                showMobileWarning={false}
                                                showTooltip={false}
                                            />
                                        </div>
                                    </div>
                                </GlowBorder>

                                <div
                                    className="animate-float animate-pop-in absolute -top-5 -left-5 hidden -rotate-6 items-center gap-2 rounded-xl bg-white p-3 shadow-lg ring-1 ring-black/5 sm:flex"
                                    style={{ animationDuration: '5s', animationDelay: '0.4s' }}
                                >
                                    <div className="flex size-8 items-center justify-center rounded-full" style={{ backgroundColor: `${brandBlue}1a`, color: brandBlue }}>
                                        <ShieldCheck className="size-4" />
                                    </div>
                                    <span className="pr-1 text-sm font-medium">Operación segura</span>
                                </div>

                                <div
                                    className="animate-float animate-pop-in absolute -right-5 -bottom-5 hidden rotate-3 items-center gap-2 rounded-xl bg-white p-3 shadow-lg ring-1 ring-black/5 sm:flex"
                                    style={{ animationDuration: '6s', animationDelay: '1s' }}
                                >
                                    <div className="flex size-8 items-center justify-center rounded-full" style={{ backgroundColor: `${bavaria}1a`, color: bavaria }}>
                                        <Beer className="size-4" />
                                    </div>
                                    <span className="pr-1 text-sm font-medium">Aliados firmes</span>
                                </div>
                            </Reveal>
                        </div>

                        <div className="mx-auto hidden max-w-6xl justify-center pb-10 sm:flex">
                            <div className="flex flex-col items-center gap-1 text-muted-foreground">
                                <span className="text-xs">Descubre más</span>
                                <ChevronDown className="size-4 animate-bounce" />
                            </div>
                        </div>
                    </section>

                    <section className="border-t border-border bg-muted/30">
                        <div className="mx-auto max-w-6xl px-6 py-16">
                            <Reveal className="mb-10 text-center">
                                <h2 className="text-3xl font-semibold tracking-tight">
                                    <GradientText colors={[brandBlue, gold, brandBlue]} animationSpeed={3.5} className="inline">
                                        Quiénes somos
                                    </GradientText>
                                </h2>
                                <p className="mt-2 text-muted-foreground">Una alianza estratégica entre servicios logísticos regionales y liderazgo nacional.</p>
                            </Reveal>

                            <div className="grid gap-6 md:grid-cols-2">
                                <Reveal delay={0}>
                                    <CompanyCard
                                        icon={Truck}
                                        accentColor={brandBlue}
                                        eyebrow="Servicios logísticos"
                                        title="EASY LOGÍSTICA S.A.S."
                                        subtitle="Soluciones tecnológicas para transporte, distribución y seguridad"
                                        description="Empresa especializada en la prestación de servicios logísticos, que integra soluciones tecnológicas para optimizar el transporte, el monitoreo, la operación de distribución, la fidelización y la seguridad. Su equipo se destaca por la excelente ejecución de cada proceso, con un firme compromiso hacia el cuidado del medio ambiente y la responsabilidad social."
                                        features={[
                                            { icon: Truck, label: 'Transporte' },
                                            { icon: MapPin, label: 'Monitoreo' },
                                            { icon: ShoppingCart, label: 'Distribución' },
                                            { icon: ShieldCheck, label: 'Seguridad' },
                                        ]}
                                    />
                                </Reveal>

                                <Reveal delay={120}>
                                    <CompanyCard
                                        icon={Beer}
                                        accentColor={bavaria}
                                        eyebrow="Aliado estratégico"
                                        title="Bavaria S.A."
                                        subtitle="La compañía cervecera líder de Colombia"
                                        description="Es la empresa cervecera más importante de Colombia y una de las compañías privadas con mayor impacto económico del país. Produce algunas de las marcas más reconocidas por los colombianos y forma parte del gigante cervecero mundial AB InBev (Anheuser-Busch InBev)."
                                        features={[
                                            { icon: Calendar, label: '+135 años de historia' },
                                            { icon: Globe, label: 'Parte de AB InBev' },
                                        ]}
                                    />
                                </Reveal>
                            </div>
                        </div>
                    </section>

                    <section className="border-t border-border">
                        <div className="mx-auto max-w-6xl px-6 py-16">
                            <Reveal className="mx-auto mb-12 max-w-2xl text-center">
                                <Badge variant="secondary" className="gap-1.5 rounded-full px-3 py-1 font-normal">
                                    <LayoutGrid className="size-3.5" />
                                    Un sistema, cuatro pilares
                                </Badge>
                                <h2 className="mt-4 text-3xl font-semibold tracking-tight">
                                    <GradientText colors={[brandBlue, gold, bavaria, gold, brandBlue]} animationSpeed={4.5} className="inline">
                                        Todo lo que gestionamos, en un solo lugar
                                    </GradientText>
                                </h2>
                                <p className="mt-2 text-muted-foreground">
                                    Los procesos que EASY LOGÍSTICA S.A.S. opera día a día, organizados y digitalizados para dar visibilidad y control total
                                    de la operación.
                                </p>
                            </Reveal>

                            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                {modules.map((module, index) => (
                                    <Reveal key={module.slug} delay={index * 100}>
                                        <SpotlightCard color={module.accent} className="group h-full rounded-lg">
                                            <Card className="relative h-full overflow-hidden p-6 transition-all duration-300 group-hover:-translate-y-1 group-hover:shadow-lg">
                                                <span
                                                    className="absolute inset-x-0 top-0 h-1 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                                                    style={{ backgroundColor: module.accent }}
                                                />
                                                <div
                                                    className="flex size-12 items-center justify-center rounded-xl transition-transform duration-300 group-hover:scale-105"
                                                    style={{ backgroundColor: `${module.accent}1a`, color: module.accent }}
                                                >
                                                    <module.icon className="size-6" />
                                                </div>

                                                <h3 className="mt-4 text-lg font-semibold text-foreground">{module.title}</h3>

                                                <ul className="mt-4 flex flex-col gap-2.5">
                                                    {flattenSubmodules(module.submodules).map((submodule) => (
                                                        <li key={submodule.slug} className="flex items-center gap-2 text-sm text-muted-foreground">
                                                            <submodule.icon className="size-3.5 shrink-0" style={{ color: module.accent }} />
                                                            {submodule.title}
                                                        </li>
                                                    ))}
                                                </ul>
                                            </Card>
                                        </SpotlightCard>
                                    </Reveal>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section className="border-t border-border bg-muted/30">
                        <div className="mx-auto max-w-6xl px-6 py-16">
                            <Reveal>
                                <div className="relative overflow-hidden rounded-3xl border border-border bg-card p-10 text-center shadow-xl sm:p-14">
                                    <span
                                        className="absolute inset-x-0 top-0 h-1"
                                        style={{ backgroundImage: `linear-gradient(90deg, ${brandBlue}, ${gold}, ${bavaria})` }}
                                    />
                                    <div
                                        className="pointer-events-none absolute -top-16 -right-16 size-64 rounded-full opacity-10 blur-3xl"
                                        style={{ backgroundColor: brandBlue }}
                                    />
                                    <div
                                        className="pointer-events-none absolute -bottom-16 -left-16 size-64 rounded-full opacity-10 blur-3xl"
                                        style={{ backgroundColor: bavaria }}
                                    />

                                    <div
                                        className="relative mx-auto mb-5 flex size-12 items-center justify-center rounded-2xl"
                                        style={{ backgroundColor: `${brandBlue}1a`, color: brandBlue }}
                                    >
                                        <Sparkles className="size-6" />
                                    </div>

                                    <h2 className="relative text-3xl font-semibold tracking-tight">
                                        <GradientText colors={[brandBlue, gold, bavaria]} animationSpeed={3} className="inline">
                                            ¿Listo para optimizar tu operación?
                                        </GradientText>
                                    </h2>
                                    <p className="relative mx-auto mt-2 max-w-xl text-muted-foreground">
                                        Ingresa a la plataforma para consultar indicadores, gestionar la operación y mantener el control de
                                        seguridad, reparto, gente y flota en tiempo real.
                                    </p>

                                    <div className="relative mt-8 flex flex-wrap justify-center gap-3">
                                        {auth.user ? (
                                            <div className="inline-flex rounded-md">
                                                <ClickSpark sparkColor={bavaria} sparkCount={10} sparkRadius={20} sparkSize={12}>
                                                    <Button
                                                        size="lg"
                                                        className="shadow-[0_8px_30px_-10px_rgba(63,122,34,0.55)] transition-shadow duration-300 hover:shadow-[0_12px_36px_-8px_rgba(63,122,34,0.7)]"
                                                        asChild
                                                    >
                                                        <Link href={route('dashboard')}>Ir al Dashboard</Link>
                                                    </Button>
                                                </ClickSpark>
                                            </div>
                                        ) : (
                                            <div className="inline-flex rounded-md">
                                                <ClickSpark sparkColor={bavaria} sparkCount={10} sparkRadius={20} sparkSize={12}>
                                                    <Button
                                                        size="lg"
                                                        className="group shadow-[0_8px_30px_-10px_rgba(63,122,34,0.55)] transition-shadow duration-300 hover:shadow-[0_12px_36px_-8px_rgba(63,122,34,0.7)]"
                                                        asChild
                                                    >
                                                        <Link href={route('login')}>
                                                            Iniciar sesión
                                                            <ArrowRight className="size-4 transition-transform duration-300 group-hover:translate-x-0.5" />
                                                        </Link>
                                                    </Button>
                                                </ClickSpark>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </Reveal>
                        </div>
                    </section>
                </main>

                <footer className="border-t border-border">
                    <div className="mx-auto max-w-6xl px-6 py-10">
                        <div className="flex flex-col items-center gap-4 sm:flex-row sm:justify-between">
                            <div className="flex items-center gap-2.5">
                                <div
                                    className="flex size-8 shrink-0 items-center justify-center rounded-lg text-white"
                                    style={{ backgroundImage: `linear-gradient(135deg, ${brandBlue}, ${brandBlueDark})` }}
                                >
                                    <Building2 className="size-4" />
                                </div>
                                <span className="text-sm font-semibold tracking-tight">EASY LOGÍSTICA S.A.S.</span>
                            </div>

                            <p className="text-center text-sm text-muted-foreground sm:text-right">
                                © {new Date().getFullYear()} EASY LOGÍSTICA S.A.S. — Servicios logísticos, aliados de Bavaria S.A. en Nariño.
                            </p>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
