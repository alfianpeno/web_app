<?php

namespace Database\Seeders;

use App\Models\NotificationTemplateLangs;
use App\Models\NotificationTemplates;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $notifications = [
            'new_customer' => 'New Customer',
            'new_invoice' => 'New Invoice',
            'new_bill' => 'New Bill',
            'new_vendor' => 'New Vendor',
            'new_revenue' => 'New Revenue',
            'new_proposal' => 'New Proposal',
            'new_payment' => 'New Payment',
            'invoice_reminder' => 'Invoice Reminder',

        ];


        $defaultTemplate = [
            'new_customer' => [
                'variables' => '{
                    "Customer Name": "customer_name",
                    "Email": "email",
                    "Password": "password", 
                    "Company Name": "company_name",
                    "App Name": "app_name",
                    "App Url": "app_url"
                    }',
                'lang' => [
                    'ar' => 'تم إنشاء عميل جديد بواسطة {customer_name}',
                    'da' => 'Ny kunde oprettet af {customer_name}',
                    'de' => 'Neuer Kunde erstellt von {customer_name}',
                    'en' => 'New Customer created by {customer_name}',
                    'es' => 'Nueva cliente creada por {customer_name}',
                    'fr' => 'Nouveau client créé par {customer_name}',
                    'it' => 'Nuovo cliente creato da {customer_name}',
                    'ja' => '{customer_name} によって作成された新しい顧客',
                    'nl' => 'Nieuwe klant gemaakt door {customer_name}',
                    'pl' => 'Nowy klient utworzony przez firmę {customer_name}',
                    'ru' => 'Новый клиент создан {customer_name}',
                    'pt' => 'Novo cliente criado por {customer_name}',
                    'tr' => 'Oluşturan Yeni Müşteri {customer_name}',
                    'zh' => '新客户创建者 {customer_name}',
                    'he' => 'לקוח חדש נוצר על ידי {customer_name}',
                    'pt-br' => 'Novo Cliente criado por {customer_name}',
                ]
            ],
            'new_invoice' => [
                'variables' => '{
                    "Invoice Name": "invoice_name",
                    "Invoice Number": "invoice_number",
                    "Invoice URL": "invoice_url",
                    "Company Name": "company_name",
                    "App Name": "app_name",
                    "App Url": "app_url"
                    }',
                'lang' => [
                    'ar' => 'فاتورة جديدة {invoice_number} تم إنشاؤها بواسطة {invoice_name}',
                    'da' => 'Ny faktura {invoice_number} oprettet af {invoice_name}',
                    'de' => 'Neue Rechnung {invoice_number} erstellt von {invoice_name}',
                    'en' => 'New Invoice {invoice_number} created by {invoice_name}',
                    'es' => 'Nueva factura {invoice_number} creada por {invoice_name}',
                    'fr' => 'Nouvelle facture {invoice_number} créée par {invoice_name}',
                    'it' => 'Nuova fattura {invoice_number} creata da {invoice_name}',
                    'ja' => '{invoice_name} によって作成された新しい請求書 {invoice_number}',
                    'nl' => 'Nieuwe factuur {invoice_number} gemaakt door {invoice_name}',
                    'pl' => 'Nowa faktura {invoice_number} utworzona przez {invoice_name}',
                    'ru' => 'Новый счет {invoice_number}, созданный {invoice_name}',
                    'pt' => 'Nova fatura {invoice_number} criada por {invoice_name}',
                    'tr' => 'Yeni fatura {invoice_number} tarafından yaratıldı {invoice_name}',
                    'zh' => '由 {invoice_name} 创建的新发票 {invoice_number}',
                    'he' => 'חשבונית חדשה {invoice_number} נוצרה על ידי {invoice_name}',
                    'pt-br' => 'Nova fatura {invoice_number} criada por {invoice_name}',
                ],
            ],
            'new_bill' => [
                'variables' => '{
                    "Bill Name": "bill_name",
                    "Bill Number": "bill_number",
                    "Bill Url": "bill_url",
                    "Company Name": "company_name",
                    "App Name": "app_name",
                    "App Url": "app_url"
                    }',
                'lang' => [
                    'ar' => 'تم إنشاء الفاتورة الجديدة {bill_number} بواسطة {bill_name}',
                    'da' => 'Ny regning {bill_number} oprettet af {bill_name}',
                    'de' => 'Neue Rechnung {bill_number} erstellt von {bill_name}',
                    'en' => 'New Bill {bill_number} created by {bill_name}',
                    'es' => 'Nueva factura {bill_number} creada por {bill_name}',
                    'fr' => 'Nouvelle facture {bill_number} créée par {bill_name}',
                    'it' => 'Nuova fattura {bill_number} creata da {bill_name}',
                    'ja' => '{bill_name} によって作成された新しい請求書 {bill_number}',
                    'nl' => 'Nieuwe factuur {bill_number} gemaakt door {bill_name}',
                    'pl' => 'Nowy rachunek {bill_number} utworzony przez {bill_name}',
                    'ru' => 'Новый счет {bill_number}, созданный пользователем {bill_name}',
                    'pt' => 'Nova fatura {bill_number} criada por {bill_name}',
                    'tr' => 'Yeni Fatura {bill_number} tarafından yaratıldı {bill_name}',
                    'zh' => '由 {bill_name} 创建的新帐单 {bill_number}',
                    'he' => 'חשבון חדש {bill_number} נוצר על ידי {bill_name}',
                    'pt-br' => 'New Bill {bill_number} criado por {bill_name}',
                ],
            ],
            'new_vendor' => [
                'variables' => '{
                    "Vendor Name": "vender_name",
                    "Email": "email",
                    "Password": "password", 
                    "Company Name": "company_name",
                    "App Name": "app_name",
                    "App Url": "app_url"
                    }',
                'lang' => [
                    'ar' => 'تم إنشاء بائع جديد بواسطة {vender_name}',
                    'da' => 'Ny leverandør oprettet af {vender_name}',
                    'de' => 'Neuer Anbieter erstellt von {vender_name}',
                    'en' => 'New Vendor created by {vender_name}',
                    'es' => 'Nuevo proveedor creado por {vender_name}',
                    'fr' => 'Nouveau fournisseur créé par {vender_name}',
                    'it' => 'Nuovo fornitore creato da {vender_name}',
                    'ja' => '{vender_name} によって作成された新しいベンダー',
                    'nl' => 'Nieuwe leverancier gemaakt door {vender_name}',
                    'pl' => 'Nowy dostawca utworzony przez {vender_name}',
                    'ru' => 'Новый поставщик создан {vender_name}',
                    'pt' => 'Novo fornecedor criado por {vender_name}',
                    'tr' => 'Oluşturan yeni satıcı {vender_name}',
                    'zh' => '新供应商创建者 {vender_name}',
                    'he' => 'ספק חדש שנוצר על ידי {vender_name}',
                    'pt-br' => 'Novo fornecedor criado por {vender_name}',
                ],
            ],
            'new_revenue' => [
                'variables' => '{
                    "Revenue name": "payment_name",
                    "Amount": "payment_amount",
                    "Payment Date": "payment_date",
                    "Company Name": "user_name",
                    "Company Name": "company_name",
                    "App Name": "app_name",
                    "App Url": "app_url"
                    }',
                'lang' => [
                    'ar' => 'تم إنشاء إيرادات جديدة لمبلغ {payment_amount} لصالح {payment_name} بواسطة {user_name}',
                    'da' => 'Ny omsætning på {payment_amount} oprettet for {payment_name} af {user_name}',
                    'de' => 'Neuer Umsatz von {payment_amount} erstellt für {payment_name} von {user_name}',
                    'en' => 'New Revenue of {payment_amount} created for {payment_name} by {user_name}',
                    'es' => 'Nuevos ingresos de {payment_amount} creados para {payment_name} por {user_name}',
                    'fr' => 'Nouveau revenu de {payment_amount} créé pour {payment_name} par {user_name}',
                    'it' => 'Nuove entrate di {payment_amount} create per {payment_name} da {user_name}',
                    'ja' => '{user_name} によって {payment_name} に作成された {payment_amount} の新しい収入',
                    'nl' => 'Nieuwe opbrengst van {payment_amount} gecreëerd voor {payment_name} door {user_name}',
                    'pl' => 'Nowy przychód w wysokości {payment_amount} utworzony dla {payment_name} przez {user_name}',
                    'ru' => 'Новый доход в размере {payment_amount} создан для {payment_name} пользователем {user_name}',
                    'pt' => 'Nova receita de {payment_amount} criada para {payment_name} por {user_name}',
                    'tr' => 'Yeni Gelir {payment_amount} için yaratıldı {payment_name} ile {user_name}',
                    'zh' => '{user_name} 为 { payment_name} 创建了 { payment_amount} 的新收入',
                    'he' => 'הכנסה חדשה בסך {payment_amount} נוצרה עבור {payment_name} על ידי {user_name}',
                    'pt-br' => 'Nova receita de {payment_amount} criada para {payment_name} por {user_name}',
                ],
            ],
            'new_proposal' => [
                'variables' => '{
                    "Proposal Name": "proposal_name",
                    "Proposal Number": "proposal_number",
                    "Proposal Url": "proposal_url",
                    "Company Name": "company_name",
                    "App Name": "app_name",
                    "App Url": "app_url"
                    }',
                'lang' => [
                    'ar' => 'تم إنشاء اقتراح جديد بواسطة {Propand_name}',
                    'da' => 'Nyt forslag oprettet af {proposal_name}',
                    'de' => 'Neues Angebot erstellt von {proposal_name}',
                    'en' => 'New Proposal created by {proposal_name}',
                    'es' => 'Nueva propuesta creada por {proposal_name}',
                    'fr' => 'Nouvelle proposition créée par {proposal_name}',
                    'it' => 'Nuova proposta creata da {proposal_name}',
                    'ja' => '{proposal_name} によって作成された新しい提案',
                    'nl' => 'Nieuw voorstel gemaakt door {proposal_name}',
                    'pl' => 'Nowa propozycja utworzona przez {proposal_name}',
                    'ru' => 'Новое предложение, созданное пользователем {proposal_name}',
                    'pt' => 'Nova proposta criada por {proposal_name}',
                    'tr' => 'Yeni Teklif tarafından oluşturuldu {proposal_name}',
                    'zh' => '新提案创建者 {proposal_name}',
                    'he' => 'הצעה חדשה נוצרה על ידי {proposal_name}',
                    'pt-br' => 'Nova Proposta criada por {proposal_name}',
                ],
            ],
            'new_payment' => [
                'variables' => '{
                    "Payment Name": "payment_name",
                    "Payment Amount": "payment_amount",
                    "Payment Type": "type", 
                    "Company Name": "company_name",
                    "App Name": "app_name",
                    "App Url": "app_url"
                    }',
                'lang' => [
                    'ar' => 'تم إنشاء دفعة جديدة بقيمة {payment_amount} لـ {payment_name} بواسطة {type}',
                    'da' => 'Ny betaling på {payment_amount} oprettet for {payment_name} af {type}',
                    'de' => 'Neue Zahlung in Höhe von {payment_amount} erstellt für {payment_name} von {type}',
                    'en' => 'New payment of {payment_amount} created for {payment_name} by {type}',
                    'es' => 'Nuevo pago de {pago_cantidad} creado para {pago_nombre} por {tipo}',
                    'fr' => 'Nouveau paiement de {payment_amount} créé pour {payment_name} par {type}',
                    'it' => 'Nuovo pagamento di {payment_amount} creato per {payment_name} da {type}',
                    'ja' => '{type} によって {payment_name} に対して作成された {payment_amount} の新しい支払い',
                    'nl' => 'Nieuwe betaling van {payment_amount} gemaakt voor {payment_name} door {type}',
                    'pl' => 'Nowa płatność w wysokości {payment_amount} została utworzona dla {payment_name} przez {type}',
                    'ru' => 'Создан новый платеж {payment_amount} для {payment_name} по {type}',
                    'pt' => 'Novo pagamento de {payment_amount} criado para {payment_name} por {type}',
                    'tr' => 'Yeni ödeme {payment_amount} için yaratıldı {payment_name} ile {type}',
                    'zh' => '{type} 为 { payment_name} 创建了一笔金额为 { payment_amount} 的新付款',
                    'he' => 'תשלום חדש בסך {payment_amount} נוצר עבור {payment_name} על ידי {type}',
                    'pt-br' => 'Novo pagamento de {payment_amount} criado para {payment_name} por {type}',
                ],
            ],
            'invoice_reminder' => [
                'variables' => '{
                    "Payment Name": "payment_name",
                    "Invoice Number": "invoice_number",
                    "Payment Due Amount": "payment_dueAmount",
                    "Payment Date": "payment_date",
                    "Company Name": "company_name",
                    "App Name": "app_name",
                    "App Url": "app_url"
                    }',
                'lang' => [
                    'ar' => 'تم إنشاء تذكير دفع جديد لـ {invoice_number} بواسطة {payment_name}',
                    'da' => 'Ny betalingspåmindelse om {invoice_number} oprettet af {payment_name}',
                    'de' => 'Neue Zahlungserinnerung von {invoice_number} erstellt von {payment_name}',
                    'en' => 'New Payment Reminder of {invoice_number} created by {payment_name}',
                    'es' => 'Nuevo recordatorio de pago de {invoice_number} creado por {payment_name}',
                    'fr' => 'Nouveau rappel de paiement de {invoice_number} créé par {payment_name}',
                    'it' => 'Nuovo sollecito di pagamento di {invoice_number} creato da {payment_name}',
                    'ja' => '{payment_name} によって作成された {invoice_number} の新しい支払い通知',
                    'nl' => 'Nieuwe betalingsherinnering van {invoice_number} gemaakt door {payment_name}',
                    'pl' => 'Nowe przypomnienie o płatności {invoice_number} utworzone przez {payment_name}',
                    'ru' => 'Новое напоминание о платеже {invoice_number}, созданное {payment_name}',
                    'pt' => 'Novo lembrete de pagamento de {invoice_number} criado por {payment_name}',
                    'tr' => 'Yeni Ödeme Hatırlatma {invoice_number} tarafından yaratıldı {payment_name}',
                    'zh' => '由 { payment_name} 创建的 {invoice_number} 的新付款提醒',
                    'he' => 'תזכורת חדשה לתשלום של {invoice_number} שנוצרה על ידי {payment_name}',
                    'pt-br' => 'Novo lembrete de pagamento de {invoice_number} criado por {payment_name}',
                ],
            ],
        ];

        $user = User::where('type','company')->first();
        foreach($notifications as $k => $n)
        {
            $ntfy = NotificationTemplates::where('slug',$k)->count();
            if($ntfy == 0)
            {
                $new = new NotificationTemplates();
                $new->name = $n;
                $new->slug = $k;
                $new->save();

                foreach($defaultTemplate[$k]['lang'] as $lang => $content)
                {
                    NotificationTemplateLangs::create(
                        [
                            'parent_id' => $new->id,
                            'lang' => $lang,
                            'variables' => $defaultTemplate[$k]['variables'],
                            'content' => $content,                                                      
                            'created_by' => $user->id,
                        ]
                    );
                }
            }
        }
    }
}
