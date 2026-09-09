import { Check, type LucideIcon, Lock } from 'lucide-react';

export type StepStatus = 'complete' | 'current' | 'upcoming' | 'locked';

export type StepDefinition = {
    id: number;
    label: string;
    description: string;
    icon: LucideIcon;
};

interface StepperProps {
    steps: StepDefinition[];
    currentStep: number;
    maxUnlockedStep: number;
    onStepClick: (step: number) => void;
}

function statusFor(stepId: number, currentStep: number, maxUnlockedStep: number): StepStatus {
    if (stepId > maxUnlockedStep) return 'locked';
    if (stepId === currentStep) return 'current';
    if (stepId < currentStep) return 'complete';
    return 'upcoming';
}

export function Stepper({ steps, currentStep, maxUnlockedStep, onStepClick }: StepperProps) {
    return (
        <div className="w-full">
            {/* Barra de progreso condensada — siempre visible, incluso en móvil */}
            <div className="mb-4 flex items-center justify-between text-sm sm:hidden">
                <span className="font-medium text-foreground">
                    Paso {currentStep} de {steps.length}: {steps[currentStep - 1]?.label}
                </span>
            </div>
            <div className="mb-6 h-1.5 w-full overflow-hidden rounded-full bg-muted sm:hidden">
                <div
                    className="h-full rounded-full bg-emerald-500 transition-all"
                    style={{ width: `${(currentStep / steps.length) * 100}%` }}
                />
            </div>

            {/* Pills de pasos — versión completa para pantallas medianas en adelante */}
            <ol className="hidden gap-2 sm:grid sm:grid-cols-4">
                {steps.map((step) => {
                    const status = statusFor(step.id, currentStep, maxUnlockedStep);
                    const Icon = step.icon;

                    return (
                        <li key={step.id}>
                            <button
                                type="button"
                                disabled={status === 'locked'}
                                onClick={() => onStepClick(step.id)}
                                className={`flex w-full items-center gap-3 rounded-xl border p-3 text-left transition-colors disabled:cursor-not-allowed disabled:opacity-50 ${
                                    status === 'current'
                                        ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-500/30 dark:bg-emerald-500/10'
                                        : status === 'complete'
                                          ? 'border-border bg-card hover:border-emerald-200'
                                          : 'border-dashed border-border bg-card'
                                }`}
                            >
                                <div
                                    className={`flex size-8 shrink-0 items-center justify-center rounded-full ${
                                        status === 'complete'
                                            ? 'bg-emerald-600 text-white'
                                            : status === 'current'
                                              ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'
                                              : 'bg-muted text-muted-foreground'
                                    }`}
                                >
                                    {status === 'complete' ? (
                                        <Check className="size-4" />
                                    ) : status === 'locked' ? (
                                        <Lock className="size-3.5" />
                                    ) : (
                                        <Icon className="size-4" />
                                    )}
                                </div>
                                <div className="min-w-0">
                                    <p className="truncate text-xs font-semibold text-foreground">
                                        Paso {step.id} · {step.label}
                                    </p>
                                    <p className="truncate text-[11px] text-muted-foreground">{step.description}</p>
                                </div>
                            </button>
                        </li>
                    );
                })}
            </ol>
        </div>
    );
}
