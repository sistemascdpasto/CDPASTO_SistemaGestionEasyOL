import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';

interface ChipMultiSelectProps {
    label: string;
    options: string[];
    value: string[];
    onChange: (value: string[]) => void;
}

export function ChipMultiSelect({ label, options, value, onChange }: ChipMultiSelectProps) {
    if (options.length === 0) {
        return null;
    }

    return (
        <div className="flex flex-col gap-1.5">
            <span className="text-xs font-medium text-muted-foreground">{label}</span>
            <ToggleGroup type="multiple" value={value} onValueChange={onChange} className="flex-wrap justify-start gap-1.5">
                {options.map((option) => (
                    <ToggleGroupItem
                        key={option}
                        value={option}
                        size="sm"
                        className="rounded-full border border-sidebar-border/70 px-3 text-xs data-[state=on]:border-primary data-[state=on]:bg-primary data-[state=on]:text-primary-foreground dark:border-sidebar-border"
                    >
                        {option}
                    </ToggleGroupItem>
                ))}
            </ToggleGroup>
        </div>
    );
}
