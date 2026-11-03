<?php

/*
| Arabic validation messages (formal Arabic). Add a rule here when a form
| starts using it, and the field's Arabic name under 'attributes'.
*/

return [

    'accepted' => 'يجب قبول :attribute.',
    'alpha_dash' => 'يجب أن يحتوي :attribute على أحرف إنجليزية وأرقام وشرطات فقط.',
    'alpha_num' => 'يجب أن يحتوي :attribute على أحرف إنجليزية وأرقام فقط.',
    'ascii' => 'يجب أن يحتوي :attribute على أحرف إنجليزية ورموز فقط.',
    'boolean' => 'قيمة :attribute غير صحيحة.',
    'confirmed' => 'تأكيد :attribute غير مطابق.',
    'date' => ':attribute ليس تاريخاً صحيحاً.',
    'decimal' => 'يجب أن يحتوي :attribute على :decimal منازل عشرية.',
    'exists' => ':attribute المختار غير موجود.',
    'gt' => [
        'numeric' => 'يجب أن يكون :attribute أكبر من :value.',
    ],
    'gte' => [
        'numeric' => 'يجب أن يكون :attribute :value أو أكثر.',
    ],
    'in' => ':attribute المختار غير صحيح.',
    'integer' => 'يجب أن يكون :attribute عدداً صحيحاً.',
    'lowercase' => 'يجب أن يُكتب :attribute بأحرف صغيرة.',
    'max' => [
        'numeric' => 'يجب ألا يزيد :attribute عن :max.',
        'string' => 'يجب ألا يزيد :attribute عن :max حرفاً.',
    ],
    'min' => [
        'numeric' => 'يجب ألا يقل :attribute عن :min.',
        'string' => 'يجب ألا يقل :attribute عن :min أحرف.',
    ],
    'numeric' => 'يجب أن يكون :attribute رقماً.',
    'regex' => 'صيغة :attribute غير صحيحة.',
    'required' => 'حقل :attribute مطلوب.',
    'string' => 'يجب أن يكون :attribute نصاً.',
    'unique' => ':attribute مستخدم من قبل.',

    'password' => [
        'letters' => 'يجب أن تحتوي :attribute على حرف واحد على الأقل.',
        'mixed' => 'يجب أن تحتوي :attribute على حرف كبير وحرف صغير على الأقل.',
        'numbers' => 'يجب أن تحتوي :attribute على رقم واحد على الأقل.',
        'symbols' => 'يجب أن تحتوي :attribute على رمز واحد على الأقل.',
        'uncompromised' => ':attribute ظهرت في تسريب بيانات، يرجى اختيار غيرها.',
    ],

    'attributes' => [
        'name' => 'الاسم',
        'username' => 'اسم المستخدم',
        'password' => 'كلمة المرور',
        'phone' => 'رقم الجوال',
        'notes' => 'الملاحظات',
        'amount' => 'المبلغ',
        'price' => 'السعر',
        'quantity' => 'الكمية',
        'description' => 'الوصف',
    ],

];
