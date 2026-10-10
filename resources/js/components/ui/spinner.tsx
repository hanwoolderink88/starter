import { useLaravelReactI18n } from "laravel-react-i18n"
import { Loader2Icon } from "lucide-react"

import { cn } from "@/lib/utils"

function Spinner({ className, ...props }: React.ComponentProps<"svg">) {
  const { t } = useLaravelReactI18n()

  return (
    <Loader2Icon
      role="status"
      aria-label={t("common.ui.loading")}
      className={cn("size-4 animate-spin", className)}
      {...props}
    />
  )
}

export { Spinner }
