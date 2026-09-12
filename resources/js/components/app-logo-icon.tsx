import { cn } from '@/lib/utils';
import { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon({ className, ...props }: ImgHTMLAttributes<HTMLImageElement>) {
    return <img src="/images/colibri-easy.png" alt="EASY LOGÍSTICA" className={cn('object-contain', className)} {...props} />;
}
